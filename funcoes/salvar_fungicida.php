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
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// propriedade ativa
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

$data             = $_POST['data'] ?? null;
$areas            = $_POST['area'] ?? [];
$fungicida        = $_POST['fungicida'] ?? null;
$fungicida_outro  = $_POST['fungicida_outro'] ?? null;
$quantidade       = $_POST['quantidade'] ?? null;
$obs              = $_POST['obs'] ?? null;

// Se o usuário escolheu "Outro", usa o nome digitado
if ($fungicida === 'outro' && !empty($fungicida_outro)) {
    $fungicida = trim($fungicida_outro);
}

if (!$data || empty($areas) || !$fungicida || !$quantidade) {
    echo json_encode(['ok' => false, 'err' => 'Preencha todos os campos obrigatórios']);
    exit;
}

try {
    $mysqli->begin_transaction();

    $tipo = "fungicida";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // detalhes (múltiplas áreas + fungicida)
    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $area_id) {
        if ($area_id) {
            $campo = "area_id";
            $valor = (int)$area_id;
            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
        }
    }

    $campo = "fungicida";
    $valor = $fungicida;
    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Fungicida salvo com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
