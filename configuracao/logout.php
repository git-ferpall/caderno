<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
@session_start();

// 1️⃣ Limpa todos os dados da sessão
$_SESSION = [];

// Remove o cookie PHP padrão da sessão
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// 2️⃣ Destrói a sessão completamente
session_destroy();

// 3️⃣ Nome real do cookie JWT
$cookieName = defined('AUTH_COOKIE') ? AUTH_COOKIE : 'AUTH_COOKIE';

// 4️⃣ Remove o cookie em todos os domínios possíveis
$domains = [
    '.frutag.com.br',           // domínio global
    'frutag.com.br',            // sem ponto
    $_SERVER['HTTP_HOST'] ?? '', // domínio atual (ex: caderno.frutag.com.br)
];

foreach ($domains as $domain) {
    if (empty($domain)) continue;
    setcookie($cookieName, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'None'
    ]);
}

// 5️⃣ Remove referência local
unset($_COOKIE[$cookieName]);

// 6️⃣ Redireciona para a tela de login (forçando refresh)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: /index.php?logout=ok');
exit;
