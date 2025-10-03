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
    echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa encontrada']);
    exit;
}
$propriedade_id = $prop['id'];

// Dados do formulário
$data        = $_POST['data'] ?? null;
$areas       = $_POST['area'] ?? [];
$inseticida  = $_POST['inseticida'] ?? null;
$quantidade  = $_POST['quantidade'] ?? null;
$obs         = $_POST['obs'] ?? null;

if (!$data || empty($areas) || !$inseticida || !$quantidade) {
    echo json_encode(['ok' => false, 'err' => 'Preencha todos os campos obrigatórios']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // Inserir apontamento principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'inseticida', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $quantidade, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir detalhes (áreas e inseticida)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $area_id) {
        $campo = "area_id";
        $valor = (string)(int)$area_id;
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
    }

    $campo = "inseticida";
    $valor = $inseticida;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Inseticida salvo com sucesso!']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
