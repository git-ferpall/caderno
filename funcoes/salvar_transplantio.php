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

if (!$user_id) {
  echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
  exit;
}

// Propriedade ativa
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
  echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa']);
  exit;
}
$propriedade_id = $prop['id'];

// Dados do form
$data          = $_POST['data'] ?? null;
$areas_origem  = $_POST['area_origem'] ?? [];
$areas_dest    = $_POST['area_destino'] ?? [];
$produto_id    = isset($_POST['produto']) ? (int)$_POST['produto'] : 0;
$quantidade    = $_POST['quantidade'] ?? null;
$obs           = $_POST['obs'] ?? null;

// Debug opcional (ver no error_log do PHP)
// error_log("Produto recebido: " . print_r($_POST['produto'], true));

if (!$data || empty($areas_origem) || empty($areas_dest) || !$produto_id) {
  echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
  exit;
}

try {
  $mysqli->begin_transaction();

  // Inserir apontamento principal (sempre pendente)
  $status = "pendente";
  $stmt = $mysqli->prepare("
    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
    VALUES (?, 'transplantio', ?, ?, ?, ?)
  ");
  $stmt->bind_param("issss", $propriedade_id, $data, $quantidade, $obs, $status);
  $stmt->execute();
  $apontamento_id = $stmt->insert_id;
  $stmt->close();

  // Inserir áreas origem
  foreach ($areas_origem as $origem) {
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "area_origem";
    $valor = (string)(int)$origem;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();
  }

  // Inserir áreas destino
  foreach ($areas_dest as $dest) {
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "area_destino";
    $valor = (string)(int)$dest;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();
  }

  // Inserir produto único
  if ($produto_id > 0) {
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    $campo = "produto_id";
    $valor = (string)$produto_id;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();
  }

  $mysqli->commit();

  echo json_encode(['ok' => true, 'msg' => 'Transplantio salvo com sucesso!']);
} catch (Exception $e) {
  $mysqli->rollback();
  echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
