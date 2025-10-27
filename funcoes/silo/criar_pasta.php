<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  $payload = verify_jwt();
  $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
  echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
  exit;
}

$nome = trim($_POST['nome'] ?? '');
$parent_id = $_POST['parent_id'] ?? null;
if ($nome === '') {
  echo json_encode(['ok' => false, 'err' => 'Nome da pasta inválido']);
  exit;
}

$stmt = $mysqli->prepare("INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo, parent_id) VALUES (?, ?, 'pasta', ?)");
$stmt->bind_param("isi", $user_id, $nome, $parent_id);
if ($stmt->execute()) {
  echo json_encode(['ok' => true, 'msg' => 'Pasta criada com sucesso.']);
} else {
  echo json_encode(['ok' => false, 'err' => 'Erro ao criar pasta.']);
}
$stmt->close();
