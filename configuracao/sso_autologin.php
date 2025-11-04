<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/configuracao_conexao.php';

@session_start();

$uid  = $_GET['uid'] ?? null;
$sig  = $_GET['sig'] ?? null;
$SSO_SECRET = '}^BNS8~o80?RyV]d';

if (!$uid || !$sig) {
    die('Parâmetros ausentes.');
}

$valid = hash_equals(hash_hmac('sha256', $uid, $SSO_SECRET), $sig);
if (!$valid) {
    die('Assinatura inválida.');
}

// 🔍 Busca o usuário/cliente correspondente
$stmt = $mysqli->prepare("SELECT id, nome, email FROM clientes WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    die('Usuário não encontrado.');
}

// ✅ Cria sessão local do Caderno
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['nome'];
$_SESSION['user_email'] = $user['email'];

// Redireciona para o painel principal
header('Location: /home/index.php');
exit;
