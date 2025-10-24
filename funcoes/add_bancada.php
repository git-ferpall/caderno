<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// identifica o usuário autenticado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// propriedade ativa
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

// dados do form
$estufa_id = $_POST['estufa_id'] ?? null;
$nome       = trim($_POST['nome'] ?? '');
$cultura    = trim($_POST['cultura'] ?? '');
$obs        = trim($_POST['obs'] ?? '');

if (!$estufa_id || $nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios ausentes']);
    exit;
}

// cria a área (bancada)
$stmt = $mysqli->prepare("
    INSERT INTO areas (user_id, propriedade_id, nome, tipo, observacoes)
    VALUES (?, ?, ?, 'bancada', ?)
");
$stmt->bind_param("iiss", $user_id, $propriedade_id, $nome, $obs);
$ok = $stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'err' => $mysqli->error]);
    exit;
}

// vincula com a estufa
$stmt = $mysqli->prepare("INSERT INTO estufa_areas (estufa_id, area_id) VALUES (?, ?)");
$stmt->bind_param("ii", $estufa_id, $new_id);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'msg' => 'Bancada adicionada com sucesso!']);
