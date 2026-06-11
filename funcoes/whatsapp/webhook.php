<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/https.php';
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/handler.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token !== '' && hash_equals(waConfig('verify'), $token)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$raw = file_get_contents('php://input') ?: '';

if (!waVerifyWebhookSignature($raw)) {
    waLog('Assinatura webhook inválida');
    http_response_code(403);
    echo 'Invalid signature';
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo 'OK';

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
}

try {
    waProcessarWebhookPayload($mysqli, $payload);
} catch (Throwable $e) {
    waLog('webhook: ' . $e->getMessage());
}
