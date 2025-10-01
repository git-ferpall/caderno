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
$data         = $_POST['data'] ?? null;
$areas        = $_POST['area'] ?? [];   // vem como array
$produto_id   = (int)($_POST['produto'] ?? 0);
$quantidade   = $_POST['quantidade'] ?? null;
$previsaoDias = $_POST['previsao'] ?? null;
$obs          = $_POST['obs'] ?? null;
$incluir_colheita = $_POST['incluir_colheita'] ?? 0;

if (!$data || empty($areas) || !$produto_id) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
    exit;
}

// Calcula previsão
$previsao = null;
if ($previsaoDias && is_numeric($previsaoDias)) {
    $dataBase = new DateTime($data);
    $dataBase->modify("+{$previsaoDias} days");
    $previsao = $dataBase->format("Y-m-d");
}

$mysqli->begin_transaction();

try {
    // 1. Insere PLANTIO
    $tipo   = "plantio";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos 
        (propriedade_id, tipo, data, quantidade, previsao, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdsss", $propriedade_id, $tipo, $data, $quantidade, $previsao, $obs, $status);
    $stmt->execute();
    $plantio_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insere detalhes (área e produto)
        $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

        // ÁREAS (pode ter várias)
        if (!empty($_POST['area']) && is_array($_POST['area'])) {
            foreach ($_POST['area'] as $area_id) {
                $campo = "area_id";
                $valor = (int)$area_id;
                $stmt->bind_param("iss", $plantio_id, $campo, $valor);
                $stmt->execute();
            }
        }

        // PRODUTO (sempre 1)
        $campo = "produto_id";
        $valor = $produto_id;
        $stmt->bind_param("iss", $plantio_id, $campo, $valor);
        $stmt->execute();

        $stmt->close();

    // 3. Se pediu colheita, cria apontamento colheita pendente
    if ($incluir_colheita == "1") {
        $tipo   = "colheita";
        $status = "pendente";
        $obsColheita = "Gerado automaticamente pelo plantio #$plantio_id";

        $stmt = $mysqli->prepare("
            INSERT INTO apontamentos 
            (propriedade_id, tipo, data, quantidade, previsao, observacoes, status)
            VALUES (?, ?, ?, NULL, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $propriedade_id, $tipo, $data, $previsao, $obsColheita, $status);
        $stmt->execute();
        $stmt->close();
    }

    $mysqli->commit();
    echo json_encode(['ok' => true, 'msg' => 'Apontamento de plantio salvo com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
