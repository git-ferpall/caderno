<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Renomeia arquivos e pastas no silo, mantendo extensão e checando duplicidade.
 * Totalmente compatível com pastas (tipo_arquivo = 'folder') e Docker (/uploads/silo/{user_id})
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔒 Autenticação via JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 🧾 Parâmetros recebidos
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca informações do item atual
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo, tipo_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $res['nome_arquivo'];
    $tipo = $res['tipo_arquivo'];
    $caminho_relativo = $res['caminho_arquivo'];

    // 🧭 Caminho base absoluto
    $base_path = realpath(__DIR__ . '/../../uploads');
    $caminho_antigo = $base_path . '/' . str_replace(['uploads/', './'], '', $caminho_relativo);

    if (!$caminho_antigo || (!file_exists($caminho_antigo) && !is_dir($caminho_antigo))) {
        elog("Arquivo/pasta física não encontrada: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 🧩 Se for arquivo, mantém extensão
    if ($tipo !== 'folder') {
        $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
        if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
            $novo_nome .= '.' . $extensao;
        }
    }

    // 🧱 Caminhos novos
    $novo_caminho_rel = dirname($caminho_relativo) . '/' . $novo_nome;
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;

    // 🚫 Checa duplicidade
    if (file_exists($novo_caminho_abs) || is_dir($novo_caminho_abs)) {
        elog("Duplicidade: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🚚 Executa o rename físico
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("Falha ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza banco
    $stmtUp = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmtUp->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmtUp->execute();
    $stmtUp->close();

    // 🔁 Se for uma pasta, atualiza os filhos (subitens)
    if ($tipo === 'folder') {
        $stmtUp2 = $mysqli->prepare("
            UPDATE silo_arquivos 
            SET caminho_arquivo = REPLACE(caminho_arquivo, ?, ?) 
            WHERE user_id = ? AND caminho_arquivo LIKE CONCAT(?, '/%')
        ");
        $stmtUp2->bind_param('ssis', $caminho_relativo, $novo_caminho_rel, $user_id, $caminho_relativo);
        $stmtUp2->execute();
        $stmtUp2->close();
    }

    echo json_encode(['ok' => true, 'msg' => ($tipo === 'folder' ? 'Pasta' : 'Arquivo') . ' renomeada com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um item com esse nome.',
        'arquivo_nao_encontrado' => 'Item não encontrado no banco de dados.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo ou pasta física não encontrada no servidor.',
        'falha_ao_renomear_arquivo' => 'Falha ao renomear o item. Verifique permissões.',
        'param_invalid' => 'Parâmetros inválidos.',
        'unauthorized' => 'Usuário não autenticado.',
        default => $e->getMessage(),
    };
    echo json_encode(['ok' => false, 'err' => $msg]);
}
