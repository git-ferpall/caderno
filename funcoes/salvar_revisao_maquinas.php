<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
  exit;
}

try {
  session_start();
  $user_id = $_SESSION['user_id'] ?? null;
  if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
  }
  if (!$user_id) throw new Exception("Usuário não autenticado");

  $log = "/tmp/debug_revisao_maquinas.txt";
  file_put_contents($log, "=== NOVA REVISÃO " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
  file_put_contents($log, print_r($_POST, true), FILE_APPEND);

  // === Propriedade ativa ===
  $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $prop = $res->fetch_assoc();
  $stmt->close();
  if (!$prop) throw new Exception("Nenhuma propriedade ativa encontrada");
  $propriedade_id = $prop['id'];

  // === Dados recebidos ===
  $data         = $_POST['data'] ?? null;
  $maquina      = $_POST['maquina'] ?? null;
  $tipo         = trim($_POST['tipo'] ?? '');
  $custo        = trim($_POST['custo'] ?? '');
  $detalhes     = trim($_POST['detalhes'] ?? '');
  $obs          = trim($_POST['obs'] ?? '');
  $prox_revisao = $_POST['prox_revisao'] ?? null;

  if (!$data || !$maquina || !$tipo)
    throw new Exception("Campos obrigatórios ausentes");

  // === Define status ===
  $status = (!empty($custo) && floatval($custo) > 0) ? 'concluido' : 'pendente';
  $valorCusto = ($custo === '' || floatval($custo) == 0) ? null : floatval($custo);

  $mysqli->begin_transaction();

  // === Inserção principal (revisão atual) ===
  $stmt = $mysqli->prepare("
    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
    VALUES (?, 'revisao_maquinas', ?, ?, ?, ?)
  ");
  $stmt->bind_param("isdss", $propriedade_id, $data, $valorCusto, $obs, $status);
  $stmt->execute();
  $apontamento_id = $stmt->insert_id;
  $stmt->close();

  file_put_contents($log, "✅ Inserido apontamento ID={$apontamento_id}\n", FILE_APPEND);

  // === Inserir detalhes principais ===
  $stmtDet = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
  $dados = [
    'maquina_id'    => $maquina,
    'tipo_revisao'  => $tipo,
    'detalhes'      => $detalhes
  ];

  foreach ($dados as $campo => $valor) {
    if (trim($valor) === '') continue;
    $stmtDet->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmtDet->execute();
  }

  // === Se houver próxima revisão, cria pendente sem custo ===
  if (!empty($prox_revisao)) {
    file_put_contents($log, "🟡 Próxima revisão prevista: {$prox_revisao}\n", FILE_APPEND);

    // Inserir revisão futura como pendente, SEM valor
    $msg = "Revisão agendada automaticamente.";
    $stmtPend = $mysqli->prepare("
      INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
      VALUES (?, 'revisao_maquinas', ?, NULL, ?, 'pendente')
    ");
    $stmtPend->bind_param("iss", $propriedade_id, $prox_revisao, $msg);
    $stmtPend->execute();
    $novo_id = $stmtPend->insert_id;
    $stmtPend->close();

    // Inserir detalhes herdados: apenas máquina e tipo
    foreach (['maquina_id' => $maquina, 'tipo_revisao' => $tipo] as $campo => $valor) {
      $stmtDet->bind_param("iss", $novo_id, $campo, $valor);
      $stmtDet->execute();
    }

    file_put_contents($log, "✅ Próxima revisão pendente criada (ID={$novo_id})\n", FILE_APPEND);
  }

  $stmtDet->close();
  $mysqli->commit();

  echo json_encode(['ok' => true, 'msg' => 'Revisão registrada com sucesso!']);
  file_put_contents($log, "✅ Finalizado com sucesso\n", FILE_APPEND);

} catch (Exception $e) {
  if (isset($mysqli)) $mysqli->rollback();
  file_put_contents("/tmp/debug_revisao_maquinas.txt", "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
