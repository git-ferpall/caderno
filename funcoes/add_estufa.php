<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

$nome = $_POST['nome'] ?? '';
$area_m2 = $_POST['area_m2'] ?? null;
$obs = $_POST['observacoes'] ?? null;
$prop = $_POST['propriedade_id'] ?? null;

if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Nome obrigatório']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO estufas (user_id, propriedade_id, nome, area_m2, observacoes) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $user_id, $prop, $nome, $area_m2, $obs);
$ok = $stmt->execute();

echo json_encode(['ok' => $ok, 'id' => $mysqli->insert_id]);
