<?php
declare(strict_types=1);
session_start();

// ===============================
// 1️⃣ Limpa sessão local
// ===============================
$_SESSION = [];

// ===============================
// 2️⃣ Remove cookie PHPSESSID
// ===============================
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

// ===============================
// 3️⃣ Remove token antigo (se existir)
// ===============================
setcookie('token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
]);

unset($_COOKIE['token']);

// ===============================
// 4️⃣ Remove AUTH_COOKIE global
// ===============================
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None'
]);

unset($_COOKIE['AUTH_COOKIE']);

// ===============================
// 5️⃣ Destrói sessão
// ===============================
session_destroy();

// ===============================
// 6️⃣ Redireciona para logout central
// ===============================
header("Location: https://frutag.com.br/logout_global.php");
exit;