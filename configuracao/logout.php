<?php
declare(strict_types=1);
session_start();

// 1️⃣ Limpa sessão
$_SESSION = [];

// 2️⃣ Remove cookie PHPSESSID
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

// 3️⃣ Remove cookie token (ESSENCIAL)
setcookie('token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
]);

unset($_COOKIE['token']);

// 4️⃣ Destrói sessão
session_destroy();

// 5️⃣ Evita cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 6️⃣ Redireciona
header("Location: /index.php?logout=ok&_=" . time());
exit;