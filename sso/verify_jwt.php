<?php
// sso/verify_jwt.php
// Valida JWT vindo do cookie AUTH_TOKEN ou header Authorization

require_once __DIR__ . '/../configuracao/env.php'; // precisa definir JWT_SECRET

function b64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function verify_jwt() {
    // 1. Captura token (primeiro do header, depois do cookie)
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $jwt = null;

    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        $jwt = $m[1];
    } elseif (!empty($_COOKIE['AUTH_TOKEN'])) {
        $jwt = $_COOKIE['AUTH_TOKEN'];
    }

    if (!$jwt) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'no_token']);
        exit;
    }

    // 2. Divide partes
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'bad_token']);
        exit;
    }

    [$h64, $p64, $s64] = $parts;
    $header  = json_decode(b64url_decode($h64), true);
    $payload = json_decode(b64url_decode($p64), true);
    $sig     = b64url_decode($s64);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'bad_payload']);
        exit;
    }

    // 3. Confere assinatura
    $check = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
    if (!hash_equals($check, $sig)) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'invalid_sig']);
        exit;
    }

    // 4. Confere expiração
    if (!empty($payload['exp']) && $payload['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'expired']);
        exit;
    }

    // Se tudo ok, retorna payload
    return $payload;
}
