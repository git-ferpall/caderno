<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$tokenAdmin = $_COOKIE[IMPERSONATE_COOKIE] ?? '';

if ($tokenAdmin === '') {
    header('Location: /home/');
    exit;
}

$claims = null;
try {
    $claims = JWT::decode($tokenAdmin, new Key(JWT_SECRET, 'HS256'));
} catch (Throwable $e) {
    $claims = null;
}

// Sempre limpa o cookie secundário
setcookie(IMPERSONATE_COOKIE, '', usuarioCookieOptions(0));

if (!$claims) {
    // Token do admin expirou durante a impersonação: encerra tudo e volta ao login
    setcookie(AUTH_COOKIE, '', usuarioCookieOptions(0));
    header('Location: /');
    exit;
}

// Restaura a sessão original
setcookie(AUTH_COOKIE, $tokenAdmin, usuarioCookieOptions(3600));

// Volta para o painel correspondente ao perfil
$perfil = usuarioPerfil($mysqli, (int)($claims->sub ?? 0));
$destino = $perfil === 'admin' ? '/home/admin_usuarios' : ($perfil === 'representante' ? '/home/meus_clientes' : '/home/');
header('Location: ' . $destino);
exit;
