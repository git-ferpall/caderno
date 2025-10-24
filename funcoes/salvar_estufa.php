<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// 1️⃣ Autenticação
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 2️⃣ Propriedade ativa
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

// 3️⃣ Dados recebidos
$area_id = $_POST['area_id'] ?? null;
$nome = trim($_POST['nome'] ?? '');
$area_m2 = trim($_POST['area_m2'] ?? '');
$obs = trim($_POST['obs'] ?? '');

if (!$area_id || $nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Preencha os campos obrigatórios']);
    exit;
}

// 4️⃣ Inserção
try {
    $mysqli->begin_transaction();

    $stmt = $mysqli->prepare("
        INSERT INTO estufas (area_id, nome, area_m2, obs)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $area_id, $nome, $area_m2, $obs);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Estufa cadastrada com sucesso!']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
