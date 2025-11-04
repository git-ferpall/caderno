<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

@session_start();

// 🔒 1️⃣ Limpa todos os dados da sessão
$_SESSION = [];

// Se existir um cookie de sessão PHP padrão, remove também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// 🔚 Destroi a sessão
session_destroy();

// 🔐 2️⃣ Expira o cookie de autenticação JWT (AUTH_COOKIE)
setcookie(AUTH_COOKIE, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',   // ✅ garante remoção global
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'None'
]);

unset($_COOKIE[AUTH_COOKIE]);

// 🔁 3️⃣ Redireciona o usuário para o login
header('Location: /index.php');
exit;
