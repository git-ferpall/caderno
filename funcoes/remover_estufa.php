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
$estufa_id = $_POST['estufa_id'] ?? null;
if (!$estufa_id) {
    echo json_encode(['ok' => false, 'err' => 'ID da estufa não informado']);
    exit;
}

try {
    $mysqli->begin_transaction();

    // 4️⃣ Valida se a estufa pertence a esta propriedade
    $check = $mysqli->prepare("
        SELECT e.id 
        FROM estufas e
        JOIN areas a ON e.area_id = a.id
        WHERE e.id = ? AND a.propriedade_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $estufa_id, $propriedade_id);
    $check->execute();
    $res = $check->get_result();
    $estufa = $res->fetch_assoc();
    $check->close();

    if (!$estufa) {
        throw new Exception('Estufa não pertence à propriedade ativa.');
    }

    // 5️⃣ Deleta bancadas manualmente (caso CASCADE não esteja ativo)
    $deleteBancadas = $mysqli->prepare("DELETE FROM bancadas WHERE estufa_id = ?");
    $deleteBancadas->bind_param("i", $estufa_id);
    $deleteBancadas->execute();
    $deleteBancadas->close();

    // 6️⃣ Deleta a estufa
    $stmt = $mysqli->prepare("DELETE FROM estufas WHERE id = ?");
    $stmt->bind_param("i", $estufa_id);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Estufa e todas as bancadas foram removidas com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
