<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

// ⚙️ Configura cabeçalhos básicos
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔐 Valida token e obtém usuário
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // 🆔 Recebe ID
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'err' => 'invalid_id']);
        exit;
    }

    // 🔎 Busca no banco
    $stmt = $mysqli->prepare("
        SELECT nome_arquivo, tipo_arquivo, caminho_arquivo 
        FROM silo_arquivos 
        WHERE id = ? AND user_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $arquivo = $res->fetch_assoc();
    $stmt->close();

    if (!$arquivo) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'err' => 'arquivo_nao_encontrado']);
        exit;
    }

    // 📂 Caminho físico do arquivo
    $caminho = "/var/www/html/" . $arquivo['caminho_arquivo'];

    if (!file_exists($caminho)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'err' => 'arquivo_fisico_nao_encontrado']);
        exit;
    }

    // ✅ Força download com cabeçalhos corretos
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $arquivo['tipo_arquivo']);
    header('Content-Disposition: attachment; filename="' . basename($arquivo['nome_arquivo']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($caminho));

    // Envia o arquivo
    readfile($caminho);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
