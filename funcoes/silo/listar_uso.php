<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

try {

    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'not_logged']);
        exit;
    }

    // limite 5GB
    $limite_gb = 5.00;
    $limite_bytes = $limite_gb * 1024 * 1024 * 1024;

    $stmt = $mysqli->prepare("
        SELECT SUM(tamanho_bytes) AS total
        FROM silo_arquivos
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $usado_bytes = intval($res['total'] ?? 0);

    $percentual = $limite_bytes > 0
        ? ($usado_bytes / $limite_bytes) * 100
        : 0;

    $percentual = min($percentual, 100);
    $percentual = round($percentual, 2);

    echo json_encode([
        'ok' => true,
        'usado_bytes' => $usado_bytes,
        'limite_bytes' => $limite_bytes,
        'percentual' => $percentual
    ]);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}