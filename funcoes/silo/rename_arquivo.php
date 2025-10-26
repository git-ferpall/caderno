<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Renomeia arquivos no silo, mantendo extensão e sincronia com o banco.
 * Compatível com Docker (uploads em /var/www/html/uploads/silo/{user_id})
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca o registro
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $res['nome_arquivo'];
    $caminho_relativo = $res['caminho_arquivo'];

    // 🧭 Monta caminho absoluto corretamente
    $base_path = realpath(__DIR__ . '/../../uploads'); // /var/www/html/uploads
    $caminho_antigo = $base_path . '/' . str_replace(['uploads/', './'], '', $caminho_relativo);

    if (!$caminho_antigo || !file_exists($caminho_antigo)) {
        elog("Arquivo físico não encontrado: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 🧩 Garante que mantenha a mesma extensão
    $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
    if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
        $novo_nome .= '.' . $extensao;
    }

    // 🗂️ Monta caminhos novos
    $novo_caminho_rel = dirname($caminho_relativo) . '/' . $novo_nome;
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;

    // 🚚 Renomeia arquivo físico
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("Falha ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza no banco
    $stmtUp = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmtUp->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode(['ok' => true, 'msg' => 'Arquivo renomeado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
