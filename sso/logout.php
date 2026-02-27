<?php
session_start();

$_SESSION = [];

// Remove sessão PHP
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => $params['secure'] ?? true,
        'httponly' => $params['httponly'] ?? true,
        'samesite' => 'Lax'
    ]);
}

// 🔥 Remove AUTH_COOKIE
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// 🔥 Remove TOKEN (ESSENCIAL)
setcookie('token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

unset($_COOKIE['AUTH_COOKIE']);
unset($_COOKIE['token']);

session_destroy();

header("Location: https://frutag.com.br/index.php?logout=1");
exit;