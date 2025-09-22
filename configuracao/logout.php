<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Expira o cookie
setcookie(AUTH_COOKIE, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

unset($_COOKIE[AUTH_COOKIE]);

// Redireciona para login
header('Location: /index.php');
exit;
