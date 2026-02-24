<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {

    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    // 🎯 DEFINA AQUI O LIMITE DO SILO (ex: 100MB)
    $limite_bytes = 100 * 1024 * 1024; // 100MB

    // 📦 Soma todos os arquivos do usuário
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

    // 🧮 Cálculo seguro da porcentagem
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

    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}