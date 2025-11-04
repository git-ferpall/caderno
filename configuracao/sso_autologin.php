<?php
/**
 * /configuracao/sso_autologin.php
 * -------------------------------
 * Autentica automaticamente usuários vindos do sistema Frutag,
 * com base em UID + assinatura HMAC, sem depender da API externa.
 * -------------------------------
 * Autor: Fabiano Amaro / Frutag
 * Última atualização: 2025-11-04
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();

$log_file = __DIR__ . '/sso_debug.log';
file_put_contents($log_file, "\n=== " . date('c') . " ===\n", FILE_APPEND);

// 🔹 Parâmetros recebidos
$uid = $_GET['uid'] ?? '';
$sig = $_GET['sig'] ?? '';

if (!$uid || !$sig) {
    file_put_contents($log_file, "❌ Parâmetros ausentes.\n", FILE_APPEND);
    die('Parâmetros inválidos.');
}

// 🔐 Mesmo segredo usado no Frutag
$SECRET = '}^BNS8~o80?RyV]d';

// 🔎 Valida assinatura HMAC
$expected_sig = hash_hmac('sha256', $uid, $SECRET);
if (!hash_equals($expected_sig, $sig)) {
    file_put_contents($log_file, "❌ Assinatura inválida. UID=$uid\n", FILE_APPEND);
    die('Assinatura inválida.');
}

// ✅ Cria sessão local (sem consultar API)
$_SESSION['user_id']   = $uid;
$_SESSION['user_nome'] = 'SSO-User-' . $uid;
$_SESSION['user_tipo'] = 'cliente';
$_SESSION['user_ativo'] = 'S';
$_SESSION['sso_login'] = true;

// 🧾 Log de sessão criada
file_put_contents($log_file, "✅ Sessão criada com sucesso:\n" . print_r($_SESSION, true) . "\n", FILE_APPEND);

// 🔁 Redireciona para o painel principal
header('Location: /home/index.php');
exit;
