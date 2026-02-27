<?php
declare(strict_types=1);
session_start();

// 1️⃣ Limpa sessão local do caderno
$_SESSION = [];

// 2️⃣ Remove PHPSESSID do caderno
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => 'caderno.frutag.com.br',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// 3️⃣ Remove AUTH_COOKIE no domínio do caderno
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

// 4️⃣ Agora chama logout do frutag
header("Location: https://frutag.com.br/login/logout.php");
exit;