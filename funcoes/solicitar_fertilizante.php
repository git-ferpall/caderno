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

try {
  $stmt = $mysqli->prepare("INSERT INTO fertilizantes (nome, status) VALUES (?, 'pendente')");
  $stmt->bind_param("s", $nome);
  $stmt->execute();
  $stmt->close();

  // opcional: registrar observação em outra tabela ou log
  // opcional: notificar admin por email

  echo json_encode(['ok' => true, 'msg' => 'Solicitação enviada com sucesso.']);
} catch (Exception $e) {
  echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar solicitação: ' . $e->getMessage()]);
}
