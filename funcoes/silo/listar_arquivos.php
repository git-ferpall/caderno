<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $arquivos = listarArquivosSilo($mysqli, $user_id);
    echo json_encode(['ok' => true, 'arquivos' => $arquivos]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
