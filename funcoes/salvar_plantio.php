<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// === Recupera o ID do usuário autenticado ===
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// === Recupera a propriedade ativa ===
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

// === Dados do formulário ===
$data              = $_POST['data'] ?? null;
$areas             = $_POST['area'] ?? [];        // múltiplas áreas
$produtos          = $_POST['produto'] ?? [];     // múltiplos produtos
$quantidade        = $_POST['quantidade'] ?? null;
$previsaoDias      = $_POST['previsao'] ?? null;
$obs               = $_POST['obs'] ?? null;
$incluir_colheita  = $_POST['incluir_colheita'] ?? 0;

// === Validação básica ===
if (!$data || empty($areas) || empty($produtos)) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos.']);
    exit;
}

// === Calcula a data de previsão, se informada ===
$previsao = null;
if ($previsaoDias && is_numeric($previsaoDias)) {
    $dataBase = new DateTime($data);
    $dataBase->modify("+{$previsaoDias} days");
    $previsao = $dataBase->format("Y-m-d");
}

// Inicia transação
$mysqli->begin_transaction();

try {
    // 1️⃣ Inserir PLANTIO principal
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

    // 2️⃣ Inserir ÁREAS
    if (!empty($areas) && is_array($areas)) {
        foreach ($areas as $area_id) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");
            $campo = "area_id";
            $valor = (string)(int)$area_id;
            $stmt->bind_param("iss", $plantio_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 3️⃣ Inserir PRODUTOS
    if (!empty($produtos) && is_array($produtos)) {
        foreach ($produtos as $produto_id) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");
            $campo = "produto_id";
            $valor = (string)(int)$produto_id;
            $stmt->bind_param("iss", $plantio_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 4️⃣ Se marcado "incluir colheita", cria apontamento COLHEITA
    if ($incluir_colheita == "1" && $previsao) {
        $tipo          = "colheita";
        $status        = "pendente";
        $obsColheita   = "Gerado automaticamente pelo plantio #$plantio_id";
        $quantidadeCol = null;

        $stmt = $mysqli->prepare("
            INSERT INTO apontamentos 
            (propriedade_id, tipo, data, quantidade, previsao, observacoes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        // Data da colheita = previsão
        $stmt->bind_param("issdsss", $propriedade_id, $tipo, $previsao, $quantidadeCol, $previsao, $obsColheita, $status);
        $stmt->execute();
        $colheita_id = $stmt->insert_id;
        $stmt->close();

        // Replica áreas e produtos do plantio na colheita
        if (!empty($areas)) {
            foreach ($areas as $area_id) {
                $campo = "area_id";
                $valor = (string)(int)$area_id;
                $stmt = $mysqli->prepare("
                    INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iss", $colheita_id, $campo, $valor);
                $stmt->execute();
                $stmt->close();
            }
        }
        if (!empty($produtos)) {
            foreach ($produtos as $produto_id) {
                $campo = "produto_id";
                $valor = (string)(int)$produto_id;
                $stmt = $mysqli->prepare("
                    INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iss", $colheita_id, $campo, $valor);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Confirma tudo
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => '✅ Apontamento de plantio salvo com sucesso!']);

} catch (Throwable $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'exception',
        'msg' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}