<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Identifica usuário
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

// Verifica duplicado em solicitações pendentes
$stmt = $mysqli->prepare("SELECT id FROM solicitacoes WHERE tipo = 'fertilizante' AND descricao = ? AND status = 'pendente' LIMIT 1");
$stmt->bind_param("s", $nome);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  echo json_encode(['ok' => false, 'msg' => 'Esse fertilizante já foi solicitado e aguarda aprovação.']);
  exit;
}
$stmt->close();

// Insere solicitação
$stmt = $mysqli->prepare("
  INSERT INTO solicitacoes (user_id, tipo, descricao, observacao, status) 
  VALUES (?, 'fertilizante', ?, ?, 'pendente')
");
$stmt->bind_param("iss", $user_id, $nome, $obs);

if ($stmt->execute()) {
  echo json_encode(['ok' => true, 'msg' => 'Solicitação de fertilizante registrada.']);
} else {
  echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar solicitação.']);
}
$stmt->close();
