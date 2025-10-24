<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Identifica usuário
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 🔍 Busca propriedade ativa
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

$propriedade_id = $prop['id'] ?? null;

// Recebe dados do formulário
$nome = trim($_POST['nome'] ?? '');
$area_m2 = trim($_POST['area_m2'] ?? '');
$obs = trim($_POST['observacoes'] ?? '');

if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Nome da estufa é obrigatório']);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO estufas (user_id, propriedade_id, nome, area_m2, observacoes)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iisss", $user_id, $propriedade_id, $nome, $area_m2, $obs);
$ok = $stmt->execute();

if ($ok) {
    echo json_encode(['ok' => true, 'id' => $mysqli->insert_id]);
} else {
    echo json_encode(['ok' => false, 'err' => $stmt->error]);
}
