<?php
declare(strict_types=1);
session_start();

// ===============================
// 1️⃣ Limpa dados da sessão
// ===============================
$_SESSION = [];

// ===============================
// 2️⃣ Remove cookie da sessão PHP
// ===============================
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'], // MUITO IMPORTANTE
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax' // ou igual ao que você usa
    ]);
}

// ===============================
// 3️⃣ Destrói sessão
// ===============================
session_destroy();

// ===============================
// 4️⃣ Remove possível JWT (SSO)
// ===============================
$cookieName = 'AUTH_COOKIE';

setcookie($cookieName, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '.frutag.com.br', // só funciona se foi criado assim
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None'
]);

unset($_COOKIE[$cookieName]);

// ===============================
// 5️⃣ Evita cache
// ===============================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ===============================
// 6️⃣ Redireciona
// ===============================
header("Location: /index.php?logout=ok&_=" . time());
exit;