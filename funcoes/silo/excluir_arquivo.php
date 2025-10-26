<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('invalid_id');

    $res = excluirArquivo($mysqli, $user_id, $id);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
