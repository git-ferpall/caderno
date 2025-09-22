<?php
// public_html/sso/userinfo.php
// Retorna informações do usuário logado com base no JWT
// Agora usando configuracao_conexao.php (mysqli)
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once __DIR__ . '/../configuracao/env.php';              // JWT_SECRET, AUTH_COOKIE
require_once __DIR__ . '/../configuracao/configuracao_conexao.php'; // cria $mysqli

function b64url_decode($d){ return base64_decode(strtr($d, '-_', '+/')); }
function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['ok'=>false,'err'=>$msg]);
    exit;
}

// 1. Captura token (Authorization ou cookie)
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

// 5. Busca informações extras no banco
$id   = (int)($payload['sub'] ?? 0);
$tipo = $payload['tipo'] ?? '';

$extra = [];

if ($tipo === 'cliente') {
    $st = $mysqli->prepare("
        SELECT 
          cli_empresa AS empresa,
          cli_razao_social AS razao_social,
          cli_cnpj_cpf AS cpf_cnpj
        FROM cliente
        WHERE cli_cod = ?
        LIMIT 1
    ");
    $st->bind_param('i', $id);
    $st->execute();
    $extra = $st->get_result()->fetch_assoc() ?: [];
    $st->close();
} elseif ($tipo === 'usuario') {
    $st = $mysqli->prepare("
        SELECT 
          usu_nome AS empresa,
          usu_nome AS razao_social,
          usu_cpf  AS cpf_cnpj
        FROM usuario
        WHERE usu_cod = ?
        LIMIT 1
    ");
    $st->bind_param('i', $id);
    $st->execute();
    $extra = $st->get_result()->fetch_assoc() ?: [];
    $st->close();
}

// 6. Resposta final
echo json_encode([
    'ok'           => true,
    'sub'          => $id,
    'tipo'         => $tipo,
    'name'         => $payload['name'] ?? null,
    'email'        => $payload['email'] ?? null,
    'empresa'      => $extra['empresa'] ?? null,
    'razao_social' => $extra['razao_social'] ?? null,
    'cpf_cnpj'     => $extra['cpf_cnpj'] ?? null,
]);
