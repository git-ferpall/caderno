<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {

    $user_id = $_SESSION['usuario_id'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'not_logged']);
        exit;
    }

    // 🔹 Limite global padrão = 1GB
    $limite_padrao_mb = 1024;

    // 🔹 Verifica se existe limite personalizado
    $stmt = $mysqli->prepare("
        SELECT limite_mb
        FROM silo_limites
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $limite_mb = isset($row['limite_mb'])
        ? (int)$row['limite_mb']
        : $limite_padrao_mb;

    $limite_bytes = $limite_mb * 1024 * 1024;

    // 🔹 Soma arquivos do usuário
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