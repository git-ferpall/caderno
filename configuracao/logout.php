<?php
session_start();

// limpa sessão
$_SESSION = [];

// remove PHPSESSID
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// 🔥 remove AUTH_COOKIE GLOBAL
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

unset($_COOKIE['AUTH_COOKIE']);

session_destroy();

// chama logout do frutag
header("Location: https://frutag.com.br/login/logout.php");
exit;