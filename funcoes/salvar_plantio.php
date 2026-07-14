<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

/* ===========================
   🔐 AUTENTICAÇÃO
=========================== */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

/* ===========================
   🏡 PROPRIEDADE ATIVA
=========================== */

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

/* ===========================
   📥 DADOS DO FORM
=========================== */

$data         = $_POST['data'] ?? null;
$areas        = array_filter($_POST['area'] ?? []);
$produtos     = array_filter($_POST['produto'] ?? []);
$quantidade   = isset($_POST['quantidade']) ? floatval($_POST['quantidade']) : null;
$unidade = $_POST['unidade'] ?? null;
$previsaoDias = $_POST['previsao'] ?? null;
$obs          = $_POST['obs'] ?? null;
$incluir_colheita = $_POST['incluir_colheita'] ?? 0;

/* ===========================
   ✅ VALIDAÇÃO
=========================== */

if (empty($data) || count($areas) === 0 || count($produtos) === 0) {
    echo json_encode([
        'ok' => false,
        'err' => 'Preencha data, ao menos uma área e um produto.'
    ]);
    exit;
}

/* ===========================
   📅 CALCULA DATA DA COLHEITA
=========================== */

$dataColheita = null;

if ($previsaoDias && is_numeric($previsaoDias)) {
    $dataBase = new DateTime($data);
    $dataBase->modify("+{$previsaoDias} days");
    $dataColheita = $dataBase->format("Y-m-d");
}

/* ===========================
   🔄 TRANSAÇÃO
=========================== */

$mysqli->begin_transaction();

try {

    /* ===========================
       🌱 1) INSERE PLANTIO
    =========================== */

    $tipo   = "plantio";
    $status = "pendente";

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
    $plantio_id = $stmt->insert_id;
    $stmt->close();


    /* ===========================
       🌾 2) DETALHES (ÁREAS)
    =========================== */

    foreach ($areas as $area_id) {
        $stmt = $mysqli->prepare("
            INSERT INTO apontamento_detalhes 
            (apontamento_id, campo, valor)
            VALUES (?, ?, ?)
        ");

        $campo = "area_id";
        $valor = (string)(int)$area_id;

        $stmt->bind_param("iss", $plantio_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }


    /* ===========================
       🌿 3) DETALHES (PRODUTOS)
    =========================== */

    foreach ($produtos as $produto_id) {
        $stmt = $mysqli->prepare("
            INSERT INTO apontamento_detalhes 
            (apontamento_id, campo, valor)
            VALUES (?, ?, ?)
        ");

        $campo = "produto_id";
        $valor = (string)(int)$produto_id;

        $stmt->bind_param("iss", $plantio_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }


    /* ===========================
       🌽 4) GERAR COLHEITA AUTOMÁTICA
    =========================== */

    if ($incluir_colheita == "1" && $dataColheita) {

        $tipo = "colheita";
        $status = "pendente";
        $obsColheita = "Gerado automaticamente pelo plantio #{$plantio_id}";
        $quantidadeCol = 0.00;

        $stmt = $mysqli->prepare("
            INSERT INTO apontamentos 
            (propriedade_id, tipo, data, quantidade, observacoes, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issdss",
            $propriedade_id,
            $tipo,
            $dataColheita,
            $quantidadeCol,
            $obsColheita,
            $status
        );

        $stmt->execute();
        $colheita_id = $stmt->insert_id;
        $stmt->close();


        // Replica detalhes
        foreach ($areas as $area_id) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes 
                (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");

            $campo = "area_id";
            $valor = (string)(int)$area_id;

            $stmt->bind_param("iss", $colheita_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }

        foreach ($produtos as $produto_id) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes 
                (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");

            $campo = "produto_id";
            $valor = (string)(int)$produto_id;

            $stmt->bind_param("iss", $colheita_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    /* ===========================
       ✅ COMMIT
    =========================== */

    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Plantio salvo com sucesso!'
    ]);

} catch (Throwable $e) {

    $mysqli->rollback();

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'err' => 'exception',
        'msg' => caderno_erro_msg($e),
        'line' => $e->getLine()
    ]);
}