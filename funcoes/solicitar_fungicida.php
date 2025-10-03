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

if (!$user_id) {
  echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
  exit;
}

$nome        = trim($_POST['nome_fungicida'] ?? '');
$fabricante  = trim($_POST['fabricante'] ?? '');
$obs         = trim($_POST['obs_fungicida'] ?? '');

if ($nome === '') {
  echo json_encode(['ok' => false, 'msg' => 'Informe o nome do fungicida']);
  exit;
}

try {
  $stmt = $mysqli->prepare("INSERT INTO solicitacoes_fungicidas (user_id, nome, fabricante, observacoes, data_solicitacao) VALUES (?, ?, ?, ?, NOW())");
  $stmt->bind_param("isss", $user_id, $nome, $fabricante, $obs);
  $stmt->execute();
  $stmt->close();

  echo json_encode(['ok' => true, 'msg' => 'Solicitação enviada com sucesso!']);
} catch (Exception $e) {
  echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar: '.$e->getMessage()]);
}
