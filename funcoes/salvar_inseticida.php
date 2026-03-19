<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

/* ===============================
🔐 USUÁRIO
=============================== */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

/* ===============================
🏡 PROPRIEDADE ATIVA
=============================== */

$stmt = $mysqli->prepare("
    SELECT id 
    FROM propriedades 
    WHERE user_id = ? 
    AND ativo = 1 
    LIMIT 1
");

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

/* ===============================
📥 DADOS DO FORM
=============================== */

$data             = $_POST['data'] ?? null;
$areas            = $_POST['area'] ?? [];
$inseticida       = $_POST['inseticida'] ?? null;
$inseticida_outro = $_POST['inseticida_outro'] ?? null;
$quantidade       = $_POST['quantidade'] ?? null;
$obs              = $_POST['obs'] ?? null;
$unidade          = $_POST['unidade'] ?? null;

/* ===============================
🧠 TRATAMENTO
=============================== */

if ($inseticida === 'outro' && !empty($inseticida_outro)) {
    $inseticida = trim($inseticida_outro);
}

/* ===============================
⚠️ VALIDAÇÃO
=============================== */

if (!$data || empty($areas) || !$inseticida || !$quantidade || !$unidade) {
    echo json_encode([
        'ok' => false, 
        'err' => 'Preencha todos os campos obrigatórios'
    ]);
    exit;
}

/* ===============================
💾 TRANSAÇÃO
=============================== */

try {

    $mysqli->begin_transaction();

    $tipo = "inseticida";
    $status = "pendente";

    /* ===============================
    1️⃣ APONTAMENTO PRINCIPAL
    =============================== */

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

    /* ===============================
    2️⃣ ÁREAS
    =============================== */

    foreach ($areas as $area_id) {

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

    /* ===============================
    3️⃣ INSETICIDA
    =============================== */

    $stmt = $mysqli->prepare("
        INSERT INTO apontamento_detalhes 
        (apontamento_id, campo, valor) 
        VALUES (?, ?, ?)
    ");

    $campo = "inseticida";
    $valor = $inseticida;

    $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
    $stmt->execute();
    $stmt->close();

    /* ===============================
    ✅ COMMIT
    =============================== */

    $mysqli->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Inseticida salvo com sucesso!'
    ]);

} catch (Exception $e) {

    $mysqli->rollback();

    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}