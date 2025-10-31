<?php
require_once __DIR__ . '/sso/env.php';
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    die('Token ausente.');
}

try {
    // 🔑 Valida o token usando o mesmo segredo da Frutag
    if (defined('JWT_ALGO') && JWT_ALGO === 'HS256') {
        $payload = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } else {
        $pub = file_get_contents(JWT_PUBLIC_KEY_PATH);
        $payload = JWT::decode($token, new Key($pub, 'RS256'));
    }

    // ✅ Define o cookie local do Caderno
    setcookie('AUTH_COOKIE', $token, [
        'expires'  => time() + 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // 🔁 Redireciona para o painel principal
    header('Location: /home/index.php');
    exit;

} catch (Throwable $e) {
    http_response_code(401);
    echo "Token inválido ou expirado: " . htmlspecialchars($e->getMessage());
}
