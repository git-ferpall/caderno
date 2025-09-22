<?php
// public_html/sso/userinfo.php
// Retorna as informações do JWT, sem acessar banco
// PHP 7.3+

@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/env.php'; // precisa ter JWT_SECRET e AUTH_COOKIE definidos

function b64url_decode($d){ return base64_decode(strtr($d, '-_', '+/')); }
function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['ok'=>false,'err'=>$msg]);
    exit;
}

// 1. Captura token (Authorization ou cookie)
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
    $jwt = $m[1];
} elseif (!empty($_COOKIE[AUTH_COOKIE])) {
    $jwt = $_COOKIE[AUTH_COOKIE];
}
if (!$jwt) fail(401, 'no_token');

// 2. Valida formato
$parts = explode('.', $jwt);
if (count($parts) !== 3) fail(401, 'bad_token');

[$h64,$p64,$s64] = $parts;
$payload = json_decode(b64url_decode($p64), true);
if (!$payload) fail(401, 'bad_payload');

// 3. Valida assinatura
$sign = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
if (!hash_equals($sign, b64url_decode($s64))) fail(401, 'sig');

// 4. Valida expiração
if (!empty($payload['exp']) && $payload['exp'] < time()) fail(401, 'exp');

// 5. Retorna claims principais + extras
echo json_encode([
    'ok'           => true,
    'sub'          => $payload['sub'] ?? null,
    'tipo'         => $payload['tipo'] ?? null,
    'name'         => $payload['name'] ?? null,
    'email'        => $payload['email'] ?? null,
    'empresa'      => $payload['empresa'] ?? null,
    'razao_social' => $payload['razao_social'] ?? null,
    'cpf_cnpj'     => $payload['cpf_cnpj'] ?? null,
]);
