<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Versão final — suporta subpastas em qualquer nível.
 * Corrige prefixos inconsistentes e usa fallback seguro.
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 📩 Parâmetros
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔍 Busca registro
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo, tipo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $item['nome_arquivo'];
    $caminho_relativo = trim($item['caminho_arquivo'], '/');
    $tipo_item = $item['tipo']; // 'arquivo' ou 'pasta'

    // 🧭 Base de uploads
    $base_path = realpath(__DIR__ . '/../../uploads');
    if (!$base_path) throw new Exception('base_invalida');

    // 🔍 Normaliza o caminho relativo
    $possiveis_caminhos = [
        $base_path . '/' . $caminho_relativo,
        $base_path . '/uploads/' . $caminho_relativo,
        $base_path . '/' . preg_replace('#^uploads/#', '', $caminho_relativo)
    ];

    $caminho_antigo = null;
    foreach ($possiveis_caminhos as $c) {
        if (file_exists($c)) {
            $caminho_antigo = $c;
            break;
        }
    }

    if (!$caminho_antigo) {
        elog("❌ Nenhum caminho válido encontrado. Tentativas:\n" . implode("\n", $possiveis_caminhos));
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 📁 Mantém extensão se for arquivo
    if ($tipo_item === 'arquivo') {
        $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
        if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
            $novo_nome .= '.' . $extensao;
        }
    }

    // 🔄 Novo caminho
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;
    $novo_caminho_rel = str_replace($base_path . '/', '', $novo_caminho_abs);

    // 🚫 Checa duplicidade
    if (file_exists($novo_caminho_abs)) {
        elog("🚫 Já existe um item com o mesmo nome: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🧱 Tenta renomear fisicamente
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("💥 Falha física ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza registro principal
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 📂 Se for pasta, atualiza caminhos dos filhos
    if ($tipo_item === 'pasta') {
        $stmt = $mysqli->prepare("
            UPDATE silo_arquivos 
            SET caminho_arquivo = REPLACE(caminho_arquivo, ?, ?) 
            WHERE user_id = ? AND caminho_arquivo LIKE CONCAT(?, '/%')
        ");
        $stmt->bind_param('ssis', $caminho_relativo, $novo_caminho_rel, $user_id, $caminho_relativo);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['ok' => true, 'msg' => '✅ Renomeado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um item com esse nome.',
        'arquivo_nao_encontrado' => 'Registro não encontrado no banco.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo ou pasta física não encontrada no servidor.',
        'falha_ao_renomear_arquivo' => 'Falha ao renomear. Verifique permissões.',
        'param_invalid' => 'Parâmetros inválidos.',
        'unauthorized' => 'Usuário não autenticado.',
        'base_invalida' => 'Diretório base de uploads não encontrado.',
        default => $e->getMessage(),
    };
    echo json_encode(['ok' => false, 'err' => $msg]);
}
