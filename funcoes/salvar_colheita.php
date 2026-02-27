<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// === Identifica usuário autenticado ===
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

// === Dados do formulário ===
$data        = $_POST['data'] ?? null;
$areas       = $_POST['area'] ?? [];     // array de áreas
$produtos    = $_POST['produto'] ?? [];  // array de produtos
$quantidade  = $_POST['quantidade'] ?? null;
$unidade    = $_POST['unidade'] ?? null;
$obs         = $_POST['obs'] ?? null;

if (!$data || empty($areas) || empty($produtos)) {
    echo json_encode(['ok' => false, 'msg' => 'Preencha os campos obrigatórios.']);
    exit;
}

// === Busca propriedade ativa ===
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa encontrada.']);
    exit;
}

$propriedade_id = $prop['id'];

// === Define status conforme quantidade ===
$status = !empty($quantidade) ? "concluido" : "pendente";

// === Início da transação ===
$mysqli->begin_transaction();

try {
    // 1. Insere o apontamento de colheita
    $tipo = "colheita";
    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos 
        (propriedade_id, tipo, data, quantidade, unidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issdsss",
        $propriedade_id,
        $tipo,
        $data,
        $quantidade,
        $unidade,
        $obs,
        $status
    );
    $stmt->execute();
    $apontamento_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insere todas as áreas
    if (!empty($areas) && is_array($areas)) {
        foreach ($areas as $area_id) {
            if (!empty($area_id)) {
                $stmt = $mysqli->prepare("
                    INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                    VALUES (?, ?, ?)
                ");
                $campo = "area_id";
                $stmt->bind_param("iss", $apontamento_id, $campo, $area_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // 3. Insere todos os produtos
    if (!empty($produtos) && is_array($produtos)) {
        foreach ($produtos as $produto_id) {
            if (!empty($produto_id)) {
                $stmt = $mysqli->prepare("
                    INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                    VALUES (?, ?, ?)
                ");
                $campo = "produto_id";
                $stmt->bind_param("iss", $apontamento_id, $campo, $produto_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Confirma tudo
    $mysqli->commit();
    echo json_encode(['ok' => true, 'msg' => 'Apontamento de colheita salvo com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar colheita: ' . $e->getMessage()]);
}
