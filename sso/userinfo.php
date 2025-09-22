<?php
// /var/www/html/sso/userinfo.php

@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../configuracao/env.php';   // onde está JWT_SECRET
require_once __DIR__ . '/../vendor/autoload.php';    // firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function bearerToken() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($h && preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        return $m[1];
    }
    if (!empty($_COOKIE[AUTH_COOKIE])) {
        return $_COOKIE[AUTH_COOKIE];
    }
    return null;
}

$jwt = bearerToken();
if (!$jwt) {
    echo json_encode(['ok' => false, 'err' => 'no_token']);
    exit;
}

try {
    $claims = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'err' => 'invalid_token']);
    exit;
}

// aqui você pode ajustar conforme payload do JWT (sub, tipo, etc.)
echo json_encode([
    'ok'           => true,
    'id'           => $claims->sub ?? null,
    'tipo'         => $claims->tipo ?? null,
    'name'         => $claims->name ?? null,
    'email'        => $claims->email ?? null,
    'empresa'      => $claims->empresa ?? null,
    'razao_social' => $claims->razao_social ?? null,
    'cpf_cnpj'     => $claims->cpf_cnpj ?? null,
]);
