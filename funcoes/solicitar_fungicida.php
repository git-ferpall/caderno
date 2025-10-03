<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Identificar usuário
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

$nome = trim($_POST['nome_fungicida'] ?? '');
$obs  = trim($_POST['obs_fungicida'] ?? '');

if ($nome === '') {
    echo json_encode(['ok' => false, 'msg' => 'Informe o nome do fungicida']);
    exit;
}

// Insere na tabela solicitacoes
$stmt = $mysqli->prepare("
    INSERT INTO solicitacoes (user_id, tipo, descricao, observacao, status, created_at) 
    VALUES (?, 'fungicida', ?, ?, 'pendente', NOW())
");
$stmt->bind_param("iss", $user_id, $nome, $obs);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'msg' => 'Solicitação de fungicida enviada com sucesso!']);
