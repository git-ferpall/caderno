<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'missing_id']);
    exit;
}

try {
    $stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prop = $result->fetch_assoc();

    if ($prop) {
        echo json_encode(['ok' => true, 'data' => $prop]);
    } else {
        http_response_code(404);
        echo json_encode(['ok' => false, 'err' => 'not_found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db', 'msg' => $e->getMessage()]);
}
