<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$id = intval($_POST['id'] ?? 0);

if (!$user_id || !$id) {
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado ou ID inválido"]);
    exit;
}

// Desativa todas
$stmt = $mysqli->prepare("UPDATE propriedades SET ativo = 0 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Ativa a escolhida
$stmt = $mysqli->prepare("UPDATE propriedades SET ativo = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

// Pega nome da propriedade ativada
$stmt = $mysqli->prepare("SELECT nome_razao FROM propriedades WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode(["ok" => true, "nome" => $res['nome_razao'] ?? ""]);
