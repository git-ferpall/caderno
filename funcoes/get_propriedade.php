<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? null;

$id = intval($_GET['id'] ?? 0);

if (!$id || !$user_id) {
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();

if ($prop) {
    echo json_encode(['ok' => true, 'propriedade' => $prop]);
} else {
    echo json_encode(['ok' => false, 'err' => 'not_found']);
}
