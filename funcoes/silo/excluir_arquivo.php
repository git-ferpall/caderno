<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
$id = intval($_POST['id'] ?? 0);

if (!$user_id || $id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

$stmt = $mysqli->prepare("SELECT caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['ok' => false, 'err' => 'not_found']);
    exit;
}

$caminho = $res['caminho_arquivo'];
if (file_exists($caminho)) unlink($caminho);

$stmt = $mysqli->prepare("DELETE FROM silo_arquivos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

echo json_encode(['ok' => true]);
