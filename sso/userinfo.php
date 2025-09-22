<?php
// public_html/sso/userinfo.php
// Retorna informações do usuário a partir do token JWT

@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../configuracao/env.php'; // aqui deve ter o define('JWT_SECRET', '...');

function b64url_decode($s) {
    return base64_decode(strtr($s, '-_', '+/'));
}

// 1. Captura o token (Authorization ou cookie)
$jwt = null;

// Header Authorization: Bearer <token>
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
    $jwt = $m[1];
}

// Cookie "token"
if (!$jwt && !empty($_COOKIE['token'])) {
    $jwt = $_COOKIE['token'];
}

if (!$jwt) {
    echo json_encode(['ok'=>false,'err'=>'no_token']); exit;
}

// 2. Valida e decodifica JWT
$parts = explode('.', $jwt);
if (count($parts) !== 3) {
    echo json_encode(['ok'=>false,'err'=>'invalid_token']); exit;
}

list($h64, $p64, $s64) = $parts;
$payload = json_decode(b64url_decode($p64), true);
if (!$payload) {
    echo json_encode(['ok'=>false,'err'=>'invalid_payload']); exit;
}

// Verifica expiração
$now = time();
if (isset($payload['exp']) && $payload['exp'] < $now) {
    echo json_encode(['ok'=>false,'err'=>'expired']); exit;
}

// Verifica assinatura HS256
$check = hash_hmac('sha256', $h64.'.'.$p64, JWT_SECRET, true);
if (!hash_equals($check, b64url_decode($s64))) {
    echo json_encode(['ok'=>false,'err'=>'bad_signature']); exit;
}

// 3. Retorna as informações disponíveis
echo json_encode([
    'ok'          => true,
    'id'          => $payload['sub'] ?? null,
    'tipo'        => $payload['tipo'] ?? null,
    'name'        => $payload['name'] ?? null,
    'email'       => $payload['email'] ?? null,
    'empresa'     => $payload['empresa'] ?? null,
    'razao_social'=> $payload['razao_social'] ?? null,
    'cpf_cnpj'    => $payload['cpf_cnpj'] ?? null,
]);
