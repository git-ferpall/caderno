<?php
declare(strict_types=1);

session_start();

// ==============================
// 1️⃣ Limpa sessão
// ==============================
$_SESSION = [];

// ==============================
// 2️⃣ Remove cookie da sessão PHP
// ==============================
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

// ==============================
// 3️⃣ Remove AUTH_COOKIE GLOBAL
// (mesmos parâmetros do login)
// ==============================
setcookie('AUTH_COOKIE', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',   // 🔥 essencial
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'               // 🔥 igual ao login
]);

unset($_COOKIE['AUTH_COOKIE']);

// ==============================
// 4️⃣ Destroi sessão
// ==============================
session_destroy();

// ==============================
// 5️⃣ Redireciona para login
// ==============================
header("Location: https://frutag.com.br/index.php?logout=1");
exit;