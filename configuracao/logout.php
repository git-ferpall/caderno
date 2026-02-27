<?php
session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path'    => '/',
        'secure'  => true,
        'httponly'=> true,
        'samesite'=> 'Lax'
    ]);
}

// REMOVE AUTH_COOKIE DO CADERNO (igual foi criado)
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => 'caderno.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

unset($_COOKIE['AUTH_COOKIE']);

session_destroy();

// depois chama logout do frutag
header("Location: https://frutag.com.br/login/logout.php");
exit;