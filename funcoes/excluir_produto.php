<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$id = intval($_POST['id'] ?? 0);
if (!$user_id || $id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Dados inválidos']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM produtos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

if ($deleted > 0) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Falha ao excluir produto']);
}
