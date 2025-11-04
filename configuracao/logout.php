<?php
declare(strict_types=1);
@session_start();
require_once __DIR__ . '/env.php';

// 1️⃣ Limpa sessão PHP
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// 2️⃣ Nome real do cookie JWT
$cookieName = defined('AUTH_COOKIE') ? AUTH_COOKIE : 'AUTH_COOKIE';

// 3️⃣ Remove o JWT com o mesmo domínio e SameSite usados no login
setcookie($cookieName, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br',   // mesmo domínio usado em gravar_cookie_sso.php
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None'
]);

// 4️⃣ Remoção local
unset($_COOKIE[$cookieName]);

// 5️⃣ Log opcional
file_put_contents(__DIR__ . '/sso_debug.log', date('c') . " - Logout forçado: AUTH_COOKIE removido\n", FILE_APPEND);

// 6️⃣ Redireciona com prevenção de cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: /index.php?logout=ok&_=' . time());
exit;
