<?php
// /var/www/html/sso/verify_jwt.php

require_once __DIR__ . '/../configuracao/env.php'; // onde está o JWT_SECRET

function b64url_decode($d) { return base64_decode(strtr($d, '-_', '+/')); }

/**
 * Verifica o JWT no Authorization Header ou no Cookie
 * Retorna payload (array) ou encerra com erro 401
 */
function verify_jwt() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $jwt = null;

    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        $jwt = $m[1];
    } elseif (!empty($_COOKIE[AUTH_COOKIE])) {
        $jwt = $_COOKIE[AUTH_COOKIE];
    }

    if (!$jwt) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'no_token']);
        exit;
    }

    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'bad_token']);
        exit;
    }

    [$h64, $p64, $s64] = $parts;
    $payload = json_decode(b64url_decode($p64), true);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'bad_payload']);
        exit;
    }

    // valida assinatura
    $sign = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
    if (!hash_equals($sign, b64url_decode($s64))) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'sig']);
        exit;
    }

    // valida expiração
    if (!empty($payload['exp']) && $payload['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'err' => 'exp']);
        exit;
    }

    return $payload;
}
