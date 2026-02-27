<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/env.php';

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

$uid = $_GET['uid'] ?? '';
$sig = $_GET['sig'] ?? '';

if (!$uid || !$sig) die('Parâmetros inválidos.');

$SECRET = '}^BNS8~o80?RyV]d';
$expected_sig = hash_hmac('sha256', $uid, $SECRET);

if (!hash_equals($expected_sig, $sig)) die('Assinatura inválida.');

$now = time();
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

setcookie('AUTH_COOKIE', $jwt, [
    'expires'  => time() + 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',  // 🔥 ESSENCIAL
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

header('Location: /home/index.php');
exit;
