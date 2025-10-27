<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'err' => 'invalid_id']);
        exit;
    }

    // 🔍 Busca o arquivo no banco
    $stmt = $mysqli->prepare("
        SELECT nome_arquivo, tipo_arquivo, caminho_arquivo 
        FROM silo_arquivos 
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'err' => 'arquivo_nao_encontrado']);
        exit;
    }

    // 🧭 Corrige caminho físico
    $path = "/var/www/html/uploads/" . ltrim($res['caminho_arquivo'], '/');

    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'err' => 'arquivo_fisico_nao_encontrado',
            'path' => $path
        ]);
        exit;
    }

    // 🔒 Evita headers já abertos
    if (ob_get_level()) ob_end_clean();

    // 🔧 Define nome e tipo de arquivo
    $nome = basename($res['nome_arquivo']);
    $tipo = $res['tipo_arquivo'] ?: 'application/octet-stream';
    $tamanho = filesize($path);

    // 📦 Envia cabeçalhos
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $tipo);
    header('Content-Disposition: attachment; filename="' . addslashes($nome) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $tamanho);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // 📥 Envia arquivo ao navegador
    readfile($path);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
