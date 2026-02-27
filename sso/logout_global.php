<?php
declare(strict_types=1);
session_start();

$_SESSION = [];

// Remove cookie sessão frutag
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax'
    ]);
}

// Remove AUTH_COOKIE compartilhado
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None'
]);

unset($_COOKIE['AUTH_COOKIE']);

session_destroy();

// 🔥 IMPORTANTE: vá para login, não para o caderno
header("Location: https://frutag.com.br/login/logout.php");
exit;