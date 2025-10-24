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

// 3️⃣ Dados
$bancada_id = $_POST['bancada_id'] ?? null;
if (!$bancada_id) {
    echo json_encode(['ok' => false, 'err' => 'ID da bancada não informado']);
    exit;
}

// 4️⃣ Exclusão
try {
    $mysqli->begin_transaction();

    // Verifica se pertence à propriedade ativa
    $check = $mysqli->prepare("
        SELECT b.id 
        FROM bancadas b
        JOIN estufas e ON b.estufa_id = e.id
        JOIN areas a ON e.area_id = a.id
        WHERE b.id = ? AND a.propriedade_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $bancada_id, $propriedade_id);
    $check->execute();
    $res = $check->get_result();
    $bancada = $res->fetch_assoc();
    $check->close();

    if (!$bancada) {
        throw new Exception('Bancada não pertence à propriedade ativa.');
    }

    $stmt = $mysqli->prepare("DELETE FROM bancadas WHERE id = ?");
    $stmt->bind_param("i", $bancada_id);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();
    echo json_encode(['ok' => true, 'msg' => 'Bancada removida com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
