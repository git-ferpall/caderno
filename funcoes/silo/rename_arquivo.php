<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Renomeia arquivos e pastas no Silo de Dados.
 * Compatível com subpastas e estrutura /uploads/silo/{user_id}/
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔒 Autenticação via JWT ou sessão
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 🧾 Parâmetros recebidos
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca o registro atual no banco
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo, tipo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('registro_nao_encontrado');

    $nome_antigo = $res['nome_arquivo'];
    $caminho_relativo = $res['caminho_arquivo'];
    $tipo = $res['tipo']; // 'arquivo' ou 'pasta'

    // 🧭 Caminho absoluto base
    $base_path = realpath(__DIR__ . '/../../uploads/silo');
    $caminho_antigo = $base_path . '/' . str_replace(['silo/', './'], '', $caminho_relativo);

    // Caso o arquivo esteja no nível mais antigo (sem "silo/")
    if (!file_exists($caminho_antigo)) {
        $base_path_alt = realpath(__DIR__ . '/../../uploads');
        $caminho_alt = $base_path_alt . '/' . $caminho_relativo;
        if (file_exists($caminho_alt)) {
            $caminho_antigo = $caminho_alt;
        } else {
            elog("Arquivo ou pasta não encontrado: $caminho_antigo");
            throw new Exception('arquivo_fisico_nao_encontrado');
        }
    }

    // 🧩 Mantém a mesma extensão caso o usuário não digite
    if ($tipo === 'arquivo') {
        $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
        if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
            $novo_nome .= '.' . $extensao;
        }
    }

    // 🧱 Define novos caminhos (relativo e absoluto)
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;
    $novo_caminho_rel = dirname($caminho_relativo) . '/' . $novo_nome;

    // 🚫 Verifica se já existe algo com esse nome
    if (file_exists($novo_caminho_abs)) {
        elog("Nome duplicado: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🚚 Renomeia fisicamente
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("Falha ao renomear $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear');
    }

    // 💾 Atualiza o registro principal
    $stmtUp = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmtUp->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmtUp->execute();
    $stmtUp->close();

    // 🪄 Se for pasta, atualiza todos os filhos
    if ($tipo === 'pasta') {
        $antigo_rel_esc = $mysqli->real_escape_string($caminho_relativo . '/');
        $novo_rel_esc = $mysqli->real_escape_string($novo_caminho_rel . '/');
        $mysqli->query("
            UPDATE silo_arquivos 
            SET caminho_arquivo = REPLACE(caminho_arquivo, '$antigo_rel_esc', '$novo_rel_esc')
            WHERE user_id = $user_id AND caminho_arquivo LIKE '$antigo_rel_esc%'
        ");
    }

    echo json_encode(['ok' => true, 'msg' => 'Nome alterado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);

    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um item com esse nome.',
        'falha_ao_renomear' => 'Falha ao renomear arquivo ou pasta.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo ou pasta física não encontrada no servidor.',
        'param_invalid' => 'Parâmetros inválidos.',
        'registro_nao_encontrado' => 'Item não encontrado no banco de dados.',
        'unauthorized' => 'Usuário não autenticado.',
        default => $e->getMessage(),
    };

    echo json_encode(['ok' => false, 'err' => $msg]);
}
