<?php
/**
 * SSO AutoLogin - Caderno de Campo
 * Recebe um token JWT do domínio frutag.com.br e cria a sessão local.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/env.php'; // ✅ Corrigido: arquivo na mesma pasta
require_once __DIR__ . '/../vendor/autoload.php'; // 🔧 caminho relativo correto para o composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    die('Token ausente na URL.');
}

try {
    // 🔑 Valida o token usando o mesmo segredo da Frutag
    if (defined('JWT_ALGO') && JWT_ALGO === 'HS256') {
        $payload = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
    } else {
        $pub = @file_get_contents(JWT_PUBLIC_KEY_PATH);
        if (!$pub) {
            throw new Exception('Chave pública não encontrada.');
        }
        $payload = JWT::decode($token, new Key($pub, 'RS256'));
    }

    // ✅ Define cookie local do Caderno
    setcookie('AUTH_COOKIE', $token, [
        'expires'  => time() + 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // (Opcional) Armazena algumas informações da sessão local
    session_start();
    $_SESSION['user_id'] = $payload->sub ?? null;
    $_SESSION['email']   = $payload->email ?? null;
    $_SESSION['name']    = $payload->name ?? null;
    $_SESSION['tipo']    = $payload->tipo ?? 'cliente';

    // 🔁 Redireciona para o painel principal
    header('Location: /home/index.php');
    exit;

} catch (Throwable $e) {
    http_response_code(401);
    echo "<h3 style='font-family:Arial;color:#b00'>Token inválido ou expirado.</h3>";
    echo "<pre style='background:#eee;padding:10px;border-radius:8px;color:#333'>"
         . htmlspecialchars($e->getMessage()) . "</pre>";
}
