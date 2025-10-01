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

// Dados do formulário
$data          = $_POST['data'] ?? null;
$areas_origem  = $_POST['area_origem'] ?? [];   // array
$areas_destino = $_POST['area_destino'] ?? [];  // array
$produto_id    = $_POST['produto'] ?? null;     // único (pode virar array se quiser)
$quantidade    = $_POST['quantidade'] ?? null;
$obs           = $_POST['obs'] ?? null;

if (!$data || empty($areas_origem) || empty($areas_destino) || !$produto_id) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // Inserir apontamento principal
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, 'transplantio', ?, ?, ?, 'pendente')
    ");
    $stmt->bind_param("isss", $propriedade_id, $data, $quantidade, $obs);
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // Inserir múltiplas áreas de origem
    if (!empty($areas_origem)) {
        foreach ($areas_origem as $area_id) {
            if (!empty($area_id)) {
                $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
                $campo = "area_origem";
                $stmt->bind_param("iss", $apontamento_id, $campo, $area_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Inserir múltiplas áreas de destino
    if (!empty($areas_destino)) {
        foreach ($areas_destino as $area_id) {
            if (!empty($area_id)) {
                $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
                $campo = "area_destino";
                $stmt->bind_param("iss", $apontamento_id, $campo, $area_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Inserir produto (único)
    if (!empty($produto_id)) {
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");
        $campo = "produto_id";
        $stmt->bind_param("iss", $apontamento_id, $campo, $produto_id);
        $stmt->execute();
        $stmt->close();
    }

    $mysqli->commit();
    echo json_encode(['ok' => true, 'msg' => 'Transplantio salvo com sucesso!']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
