<?php
declare(strict_types=1);
@session_start();

require_once __DIR__ . '/env.php';

// 1️⃣ Limpa completamente a sessão PHP
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// 2️⃣ Destrói a sessão no servidor
session_destroy();

// 3️⃣ Remove cookies JWT e variantes
$cookieNames = [
    defined('AUTH_COOKIE') ? AUTH_COOKIE : 'AUTH_COOKIE',
    'token', // fallback
    'PHPSESSID' // importante: remove a sessão PHP também
];

$domains = [
    $_SERVER['HTTP_HOST'] ?? '',
    'frutag.com.br',
    '.frutag.com.br',
    'caderno.frutag.com.br'
];

foreach ($cookieNames as $cookie) {
    foreach ($domains as $domain) {
        if (empty($domain)) continue;
        setcookie($cookie, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => $domain,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
    }
    unset($_COOKIE[$cookie]);
}

// 4️⃣ Log (debug opcional)
file_put_contents(__DIR__ . '/sso_debug.log', date('c') . " - Logout global executado\n", FILE_APPEND);

// 5️⃣ Força reload e previne cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 6️⃣ Redireciona para login (com token único pra evitar cache)
header('Location: /index.php?logout=ok&_=' . time());
exit;
