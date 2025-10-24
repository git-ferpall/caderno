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

$estufa_id = (int)($_POST['estufa_id'] ?? 0);
$nome      = trim($_POST['nome'] ?? '');
$cultura   = trim($_POST['cultura'] ?? '');
$obs       = trim($_POST['obs'] ?? '');

if (!$estufa_id || $nome === '') {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios']);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO bancadas (estufa_id, nome, cultura, obs)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $estufa_id, $nome, $cultura, $obs);
$ok = $stmt->execute();
$id = $stmt->insert_id;
$stmt->close();

echo json_encode(['ok' => $ok, 'id' => $id]);
