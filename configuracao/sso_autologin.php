<?php
/**
 * SSO AutoLogin - Caderno de Campo
 * ---------------------------------
 * Autentica via link assinado enviado pelo portal Frutag.
 * Não consulta o banco local, valida via API.
 */

@session_start();
require_once __DIR__ . '/../configuracao/env.php'; // onde está o JWT_SECRET
$log = __DIR__ . '/sso_debug.log'; // ← mantém igual se este arquivo está dentro de /configuracao/


file_put_contents($log, "\n=== " . date('c') . " ===\n", FILE_APPEND);

$uid = $_GET['uid'] ?? null;
$sig = $_GET['sig'] ?? null;

if (!$uid || !$sig) {
    file_put_contents($log, "❌ Parâmetros ausentes (uid/sig)\n", FILE_APPEND);
    die('Parâmetros ausentes.');
}

// 🔑 Mesmo segredo do Frutag
$SSO_SECRET = '}^BNS8~o80?RyV]d';

// 🔒 Valida assinatura
$expected = hash_hmac('sha256', $uid, $SSO_SECRET);
if (!hash_equals($expected, $sig)) {
    file_put_contents($log, "❌ Assinatura inválida para UID=$uid\n", FILE_APPEND);
    die('Assinatura inválida.');
}

// 🌐 Chama API do Frutag para buscar dados do usuário
$api_url = "https://frutag.com.br/sso/userinfo.php?id=" . urlencode($uid);
$response = @file_get_contents($api_url);

if (!$response) {
    file_put_contents($log, "❌ Falha ao chamar API: $api_url\n", FILE_APPEND);
    die('Erro ao consultar API.');
}

$data = json_decode($response, true);
if (empty($data['ok'])) {
    file_put_contents($log, "❌ API retornou erro: $response\n", FILE_APPEND);
    die('Usuário inválido.');
}

// ✅ Cria sessão local no Caderno
$_SESSION['user_id']   = $data['user']['id'];
$_SESSION['user_nome'] = $data['user']['nome'];
$_SESSION['user_email'] = $data['user']['email'];
$_SESSION['user_tipo'] = $data['user']['tipo'];
$_SESSION['sso_from']  = 'frutag';

file_put_contents($log, "✅ Login OK: " . print_r($data['user'], true) . "\n", FILE_APPEND);

// 🚀 Redireciona para o painel principal
header('Location: /home/index.php');
exit;
