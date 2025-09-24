<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// Recupera user_id da sessão ou JWT
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["ok" => false, "error" => "ID inválido"]);
    exit;
}

// Verifica se a propriedade realmente pertence ao usuário
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["ok" => false, "error" => "Propriedade não encontrada"]);
    exit;
}
$stmt->close();

// Desativa todas as propriedades do usuário
$stmt = $mysqli->prepare("UPDATE propriedades SET ativo = 0 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Ativa apenas a propriedade escolhida
$stmt = $mysqli->prepare("UPDATE propriedades SET ativo = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$updated = $stmt->affected_rows;
$stmt->close();

if ($updated > 0) {
    echo json_encode(["ok" => true, "id" => $id]);
} else {
    echo json_encode(["ok" => false, "error" => "Falha ao ativar propriedade"]);
}
