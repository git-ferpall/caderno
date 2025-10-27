<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) throw new Exception('id_invalid');

    $stmt = $mysqli->prepare("
        SELECT parent_id, nome_arquivo 
        FROM silo_arquivos 
        WHERE id = ? AND user_id = ? AND tipo = 'pasta'
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('not_found');

    echo json_encode([
        'ok' => true,
        'parent_id' => $res['parent_id'],
        'nome_parent' => $res['nome_arquivo']
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
