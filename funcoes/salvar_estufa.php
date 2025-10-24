<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

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

$nome     = trim($_POST['nome'] ?? '');
$area_m2  = trim($_POST['area_m2'] ?? '');
$obs      = trim($_POST['obs'] ?? '');

if ($nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Nome da estufa obrigatório']);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO estufas (propriedade_id, nome, area_m2, obs)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $propriedade_id, $nome, $area_m2, $obs);
$ok = $stmt->execute();
$id = $stmt->insert_id;
$stmt->close();

echo json_encode(['ok' => $ok, 'id' => $id]);
