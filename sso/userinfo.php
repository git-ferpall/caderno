<?php
// public_html/sso/userinfo.php
// Retorna informações detalhadas do usuário logado com base no JWT

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/userinfo_error.log');

@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../configuracao/configuracao_conexao.php'; // usa conexão centralizada
require_once __DIR__ . '/../configuracao/env.php';                 // precisa ter JWT_SECRET e AUTH_COOKIE definidos

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

// 5. Busca informações extras no banco (se necessário)
$id   = (int)($payload['sub'] ?? 0);
$tipo = $payload['tipo'] ?? '';

$empresa = $payload['empresa'] ?? null;
$razao   = $payload['razao_social'] ?? null;
$cpfcnpj = $payload['cpf_cnpj'] ?? null;

try {
    if ($tipo === 'cliente') {
        $st = $pdo->prepare("
            SELECT cli_empresa, cli_razao_social, cli_cnpj_cpf
            FROM cliente
            WHERE cli_cod = :id
            LIMIT 1
        ");
        $st->execute([':id'=>$id]);
        $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $empresa = $extra['cli_empresa'] ?? $empresa;
        $razao   = $extra['cli_razao_social'] ?? $razao;
        $cpfcnpj = $extra['cli_cnpj_cpf'] ?? $cpfcnpj;
    } elseif ($tipo === 'usuario') {
        $st = $pdo->prepare("
            SELECT usu_nome AS empresa, usu_nome AS razao_social, usu_cpf AS cpf_cnpj
            FROM usuario
            WHERE usu_cod = :id
            LIMIT 1
        ");
        $st->execute([':id'=>$id]);
        $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $empresa = $extra['empresa'] ?? $empresa;
        $razao   = $extra['razao_social'] ?? $razao;
        $cpfcnpj = $extra['cpf_cnpj'] ?? $cpfcnpj;
    }
} catch (Throwable $e) {
    error_log("Erro DB userinfo: ".$e->getMessage());
    fail(500, 'db');
}

// 6. Retorna claims + extras
echo json_encode([
    'ok'           => true,
    'sub'          => $id,
    'tipo'         => $tipo,
    'name'         => $payload['name'] ?? null,
    'email'        => $payload['email'] ?? null,
    'empresa'      => $empresa,
    'razao_social' => $razao,
    'cpf_cnpj'     => $cpfcnpj,
]);
