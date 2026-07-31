<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/https.php';
require_once __DIR__ . '/sso_autologin_helper.php';

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

$uid = (string) ($_GET['uid'] ?? '');
$exp = (string) ($_GET['exp'] ?? '');
$sig = (string) ($_GET['sig'] ?? '');

if ($uid === '' || $sig === '') {
    http_response_code(400);
    exit('Parâmetros inválidos.');
}

$SECRET = JWT_SECRET;
$now = time();

if ($exp === '') {
    http_response_code(400);
    exit('Link expirado ou inválido. Solicite um novo acesso.');
}

$expTs = (int) $exp;
if ($expTs < $now) {
    http_response_code(400);
    exit('Link expirado. Solicite um novo acesso.');
}
if ($expTs > $now + SSO_AUTOLOGIN_MAX_AGE_SECONDS) {
    http_response_code(400);
    exit('Link inválido.');
}

$expectedSig = hash_hmac('sha256', $uid . '|' . $exp, $SECRET);
if (!hash_equals($expectedSig, $sig)) {
    http_response_code(403);
    exit('Assinatura inválida.');
}

$payload = [
    'iss' => 'https://frutag.com.br',
    'aud' => 'frutag-apps',
    'iat' => $now,
    'exp' => $now + 3600,
    'sub' => $uid,
    'tipo' => 'cliente',
    'name' => 'SSO-' . $uid,
];

$jwt = JWT::encode($payload, $SECRET, 'HS256');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

setcookie(AUTH_COOKIE, $jwt, [
    'expires'  => $now + 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Location: /home');
exit;
