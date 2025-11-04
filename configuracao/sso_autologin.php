<?php
/**
 * /configuracao/sso_autologin.php
 * Integração SSO Frutag → Caderno de Campo
 * Autor: Fabiano Amaro / Frutag
 * Atualizado em: 2025-11-04
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$log = __DIR__ . '/sso_debug.log';
file_put_contents($log, date('c') . " - Início do autologin\n", FILE_APPEND);

// 🔍 Recebe token (não é mais usado, mas mantido para compatibilidade)
$token = $_GET['token'] ?? null;

// 🧠 Busca dados do usuário autenticado diretamente pela API Frutag
$api_url = "https://frutag.com.br/sso/userinfo.php";
file_put_contents($log, "Consultando API: $api_url\n", FILE_APPEND);

$response = @file_get_contents($api_url);
if ($response === false) {
    file_put_contents($log, "Erro ao consultar API (file_get_contents falhou)\n", FILE_APPEND);
    die("Erro ao consultar API.");
}

$data = json_decode($response, true);
file_put_contents($log, "Resposta API:\n" . print_r($data, true) . "\n", FILE_APPEND);

if (empty($data['ok']) || !$data['ok']) {
    die("Usuário não autenticado.");
}

$user = $data['user'] ?? [];
if (empty($user['id'])) {
    die("Dados inválidos do usuário.");
}

// ✅ Define o cookie local de autenticação no Caderno
session_start();
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_nome'] = $user['nome'];
$_SESSION['user_tipo'] = $user['tipo'];
$_SESSION['user_ativo'] = $user['ativo'];

file_put_contents($log, "Sessão criada com sucesso: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// 🔁 Redireciona para o painel principal
header('Location: /home/index.php');
exit;
