<?php
declare(strict_types=1);
@session_start();

require_once __DIR__ . '/env.php';

// === 1️⃣ Limpa sessão PHP ===
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// === 2️⃣ Força remoção dos cookies JWT ===
$cookieNames = [
    defined('AUTH_COOKIE') ? AUTH_COOKIE : 'AUTH_COOKIE',
    'token' // fallback caso outro nome tenha sido usado
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

// === 3️⃣ Força recarregar o navegador ===
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: /index.php?logout=ok&_='.time()); // timestamp impede cache
exit;
