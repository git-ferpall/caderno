<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/agrofit.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int) ($payload['sub'] ?? 0);
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    echo json_encode(fsSincronizarAgrofitDesdeCatalogo($mysqli), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('fitossanitaria/sincronizar_agrofit.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao sincronizar AGROFIT.'], JSON_UNESCAPED_UNICODE);
}
