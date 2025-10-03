<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  $payload = verify_jwt();
  $user_id = $payload['sub'] ?? null;
}

$nome = trim($_POST['nome'] ?? '');
$obs  = trim($_POST['obs'] ?? '');

if ($nome === '') {
  echo json_encode(['ok' => false, 'msg' => 'O nome do fertilizante é obrigatório.']);
  exit;
}

// Insere como pendente
$stmt = $mysqli->prepare("INSERT INTO fertilizantes (nome, status) VALUES (?, 'pendente')");
$stmt->bind_param("s", $nome);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'msg' => 'Solicitação enviada. Você receberá retorno por e-mail após avaliação.']);
