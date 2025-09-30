<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Autenticação
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

// Dados do formulário
$data        = $_POST['data'] ?? null;
$area_id     = (int)($_POST['area'] ?? 0);
$fertilizante = $_POST['fertilizante'] ?? null;
$quantidade  = $_POST['quantidade'] ?? null;
$obs         = $_POST['obs'] ?? null;

if (!$data || !$area_id || !$fertilizante) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // Inserir apontamento
    $tipo = "fertilizante";
    $status = "registro"; // status fixo

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $propriedade_id, $tipo, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir detalhes
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
    
    $campo = "area_id";
    $valor = $area_id;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();

    $campo = "fertilizante";
    $valor = $fertilizante;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de fertilizante salvo com sucesso!']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
