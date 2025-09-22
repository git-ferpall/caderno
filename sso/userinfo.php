<?php
// public_html/sso/userinfo.php
// Retorna informações detalhadas do usuário logado com base no JWT
// PHP 7.3+

@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../configuracao/env.php';            // JWT_SECRET e AUTH_COOKIE
require_once __DIR__ . '/../configuracao/conexao_frutag.php'; // conexão remota fruta169_frutag

function b64url_decode($d){ return base64_decode(strtr($d, '-_', '+/')); }

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['ok'=>false,'err'=>$msg]);
    exit;
}

// 1. Captura token
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = null;
if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
    $jwt = $m[1];
} elseif (!empty($_COOKIE[AUTH_COOKIE])) {
    $jwt = $_COOKIE[AUTH_COOKIE];
}
if (!$jwt) fail(401, 'no_token');

// 2. Valida formato
$parts = explode('.', $jwt);
if (count($parts) !== 3) fail(401, 'bad_token');

[$h64,$p64,$s64] = $parts;
$payload = json_decode(b64url_decode($p64), true);
if (!$payload) fail(401, 'bad_payload');

// 3. Valida assinatura
$sign = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
if (!hash_equals($sign, b64url_decode($s64))) fail(401, 'sig');

// 4. Valida expiração
if (!empty($payload['exp']) && $payload['exp'] < time()) fail(401, 'exp');

// 5. Busca infos extras no banco remoto
$id   = (int)($payload['sub'] ?? 0);
$tipo = $payload['tipo'] ?? '';

$extra = [];

try {
    if ($tipo === 'cliente') {
        $st = $pdo_frutag->prepare("
            SELECT 
                cli_empresa     AS empresa,
                cli_razao_social AS razao_social,
                cli_cnpj_cpf    AS cpf_cnpj
            FROM cliente
            WHERE cli_cod = :id
            LIMIT 1
        ");
        $st->execute([':id'=>$id]);
        $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif ($tipo === 'usuario') {
        $st = $pdo_frutag->prepare("
            SELECT 
                usu_nome AS empresa,
                usu_nome AS razao_social,
                usu_cpf  AS cpf_cnpj
            FROM usuario
            WHERE usu_cod = :id
            LIMIT 1
        ");
        $st->execute([':id'=>$id]);
        $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    fail(500, 'db_frutag: '.$e->getMessage());
}

// 6. Resposta final
echo json_encode([
    'ok'           => true,
    'sub'          => $payload['sub'] ?? null,
    'tipo'         => $payload['tipo'] ?? null,
    'name'         => $payload['name'] ?? null,
    'email'        => $payload['email'] ?? null,
    'empresa'      => $extra['empresa'] ?? null,
    'razao_social' => $extra['razao_social'] ?? null,
    'cpf_cnpj'     => $extra['cpf_cnpj'] ?? null,
]);
