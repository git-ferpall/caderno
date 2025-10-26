<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    $id = intval($_POST['id'] ?? 0);
    if (!$user_id || !$id) throw new Exception('unauthorized');

    $stmt = $mysqli->prepare("SELECT caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('not_found');
    if (file_exists($res['caminho_arquivo'])) unlink($res['caminho_arquivo']);

    $stmt = $mysqli->prepare("DELETE FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
