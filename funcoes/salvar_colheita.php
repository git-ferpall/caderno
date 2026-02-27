<?php

// 🔒 Nunca exibir erro em retorno JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// 🔥 Limpa qualquer saída anterior
if (ob_get_length()) {
    ob_clean();
}

session_start();

/* ==========================
   🔐 AUTENTICAÇÃO
========================== */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

/* ==========================
   📥 DADOS DO FORM
========================== */

$data       = $_POST['data'] ?? null;
$areas      = $_POST['area'] ?? [];
$produtos   = $_POST['produto'] ?? [];
$quantidade = isset($_POST['quantidade']) && $_POST['quantidade'] !== ''
              ? floatval($_POST['quantidade'])
              : null;
$unidade    = $_POST['unidade'] ?? null;
$obs        = $_POST['obs'] ?? null;

/* ==========================
   ✅ VALIDAÇÃO
========================== */

if (
    empty($data) ||
    empty($areas) ||
    empty($produtos) ||
    $quantidade === null ||
    empty($unidade)
) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Preencha todos os campos obrigatórios.'
    ]);
    exit;
}

/* ==========================
   🏡 PROPRIEDADE ATIVA
========================== */

$stmt = $mysqli->prepare("
    SELECT id 
    FROM propriedades 
    WHERE user_id = ? 
    AND ativo = 1 
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Nenhuma propriedade ativa encontrada.'
    ]);
    exit;
}

$propriedade_id = $prop['id'];

/* ==========================
   📌 STATUS
========================== */

$status = $quantidade > 0 ? "concluido" : "pendente";

/* ==========================
   🔄 TRANSAÇÃO
========================== */

$mysqli->begin_transaction();

try {

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

    // 🔁 ÁREAS
    foreach ($areas as $area_id) {
        if (!empty($area_id)) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes
                (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");
            $campo = "area_id";
            $valor = (string)(int)$area_id;

            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 🔁 PRODUTOS
    foreach ($produtos as $produto_id) {
        if (!empty($produto_id)) {
            $stmt = $mysqli->prepare("
                INSERT INTO apontamento_detalhes
                (apontamento_id, campo, valor)
                VALUES (?, ?, ?)
            ");
            $campo = "produto_id";
            $valor = (string)(int)$produto_id;

            $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    $mysqli->commit();

    echo json_encode([
        'ok'  => true,
        'msg' => 'Apontamento de colheita salvo com sucesso!'
    ]);
    exit;

} catch (Throwable $e) {

    $mysqli->rollback();

    echo json_encode([
        'ok'  => false,
        'msg' => 'Erro ao salvar colheita.'
    ]);
    exit;
}