<?php
declare(strict_types=1);

require_once __DIR__ . '/configuracao/env.php'; // garante que AUTH_COOKIE está definido

// Expira o cookie AUTH_COOKIE
setcookie(AUTH_COOKIE, '', [
    'expires'  => time() - 3600, // expira no passado
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);

// Também remove da superglobal (só pra garantir)
unset($_COOKIE[AUTH_COOKIE]);

// Redireciona para a tela de login
header('Location: /index.php');
exit;
