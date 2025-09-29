<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../configuracao/env.php';
require_once __DIR__ . '/../configuracao/conexao_frutag.php';

function b64url_decode($d){ return base64_decode(strtr($d, '-_', '+/')); }

function fail($msg) {
    echo "<p style='color:red'>⚠ $msg</p>";
    exit;
}

// 1. Captura token
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$jwt = null;

if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
    $jwt = $m[1];
} elseif (!empty($_COOKIE[AUTH_COOKIE])) {
    $jwt = $_COOKIE[AUTH_COOKIE];
} elseif (!empty($_COOKIE['token'])) {
    $jwt = $_COOKIE['token'];
} elseif (!empty($_GET['token'])) {
    $jwt = $_GET['token'];
}

if (!$jwt) fail("Nenhum token encontrado (cookie ou ?token=)");

// 2. Mostra o token cru
echo "<h3>Token recebido:</h3>";
echo "<pre>".htmlspecialchars($jwt)."</pre>";

// 3. Valida formato
$parts = explode('.', $jwt);
if (count($parts) !== 3) fail("Formato inválido de token");

[$h64,$p64,$s64] = $parts;
$payload = json_decode(b64url_decode($p64), true);

// 4. Mostra payload decodificado
echo "<h3>Payload decodificado:</h3><pre>";
var_dump($payload);
echo "</pre>";

// 5. Valida assinatura
$sign = hash_hmac('sha256', "$h64.$p64", JWT_SECRET, true);
if (!hash_equals($sign, b64url_decode($s64))) fail("Assinatura inválida");

// 6. Valida expiração
if (!empty($payload['exp']) && $payload['exp'] < time()) {
    fail("Token expirado em ".date('Y-m-d H:i:s',$payload['exp']));
}

// 7. Consulta banco remoto
$id   = (int)($payload['sub'] ?? 0);
$tipo = $payload['tipo'] ?? '';
$extra = [];

if ($id && $tipo) {
    try {
        if ($tipo === 'cliente') {
            $st = $pdo_frutag->prepare("SELECT cli_empresa, cli_razao_social, cli_cnpj_cpf FROM cliente WHERE cli_cod = :id LIMIT 1");
            $st->execute([':id'=>$id]);
            $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        } elseif ($tipo === 'usuario') {
            $st = $pdo_frutag->prepare("SELECT usu_nome, usu_cpf FROM usuario WHERE usu_cod = :id LIMIT 1");
            $st->execute([':id'=>$id]);
            $extra = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        echo "<p style='color:red'>Erro ao buscar no banco remoto: ".$e->getMessage()."</p>";
    }
}

// 8. Mostra infos do banco
echo "<h3>Dados do banco remoto:</h3><pre>";
var_dump($extra);
echo "</pre>";

// 9. Resposta final JSON
echo "<h3>Resposta final (JSON simulada):</h3><pre>";
echo json_encode([
    'ok'=>true,
    'payload'=>$payload,
    'extra'=>$extra
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
echo "</pre>";
