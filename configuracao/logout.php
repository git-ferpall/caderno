<?php
declare(strict_types=1);
session_start();

// 1️⃣ Limpa sessão local
$_SESSION = [];

// 2️⃣ Remove PHPSESSID do caderno
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, '/');
}

// 3️⃣ Remove AUTH_COOKIE criado no domínio do caderno
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => 'caderno.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// 4️⃣ Remove AUTH_COOKIE global (caso exista)
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

// 5️⃣ Agora chama logout do frutag
header("Location: https://frutag.com.br/login/logout.php");
exit;