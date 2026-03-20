<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
header('Content-Type: application/json; charset=utf-8');

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

    // 🧭 Resolve caminho físico a partir da pasta local de uploads
    $uploadsBase = realpath(__DIR__ . '/../../uploads');
    if ($uploadsBase === false) {
        throw new RuntimeException('uploads_base_invalida');
    }

    $caminhoRelativo = trim((string)($res['caminho_arquivo'] ?? ''), "/\\");
    $path = $uploadsBase . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoRelativo);
    $pathReal = realpath($path);

    // Evita path traversal e garante que o arquivo está dentro de uploads
    if ($pathReal === false || strpos($pathReal, $uploadsBase . DIRECTORY_SEPARATOR) !== 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'err' => 'caminho_invalido']);
        exit;
    }

    if (!file_exists($pathReal)) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'err' => 'arquivo_fisico_nao_encontrado'
        ]);
        exit;
    }

    // 🔒 Evita headers já abertos
    if (ob_get_level()) ob_end_clean();

    // 🔧 Define nome e tipo de arquivo
    $nome = basename($res['nome_arquivo']);
    $tipo = $res['tipo_arquivo'] ?: 'application/octet-stream';
    $tamanho = filesize($pathReal);
    $nomeSeguroHeader = str_replace(['"', "\r", "\n"], '', $nome);
    $nomeUtf8 = rawurlencode($nome);

    // 📦 Envia cabeçalhos
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $tipo);
    header('Content-Disposition: attachment; filename="' . $nomeSeguroHeader . '"; filename*=UTF-8\'\'' . $nomeUtf8);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $tamanho);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // 📥 Envia arquivo ao navegador
    readfile($pathReal);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
