<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');

session_start();

// Pega user_id
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// Descobre propriedade ativa
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
$produto_id  = (int)($_POST['produto'] ?? 0);
$quantidade  = $_POST['quantidade'] ?? null;
$previsao    = (int)($_POST['previsao'] ?? 0);
$obs         = $_POST['obs'] ?? null;

if (!$data || !$area_id || !$produto_id) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
    exit;
}

$mysqli->begin_transaction();

try {
    // Salva apontamento principal
    $stmt = $mysqli->prepare("INSERT INTO apontamentos (user_id, propriedade_id, data) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $propriedade_id, $data);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Salva detalhes do plantio
    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes (apontamento_id, area_id, produto_id, quantidade, previsao_colheita, observacoes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iii sis", $apontamento_id, $area_id, $produto_id, $quantidade, $previsao, $obs);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
