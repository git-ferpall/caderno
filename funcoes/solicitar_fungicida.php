<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Pega user_id
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

// Dados do formulário
$nome = trim($_POST['nome_fungicida'] ?? '');   // <-- cuidado: no seu HTML está "nome_fungicida"
$observacao = trim($_POST['obs_fungicida'] ?? ''); // <-- e "obs_fungicida"

if ($nome === '') {
    echo json_encode(['ok' => false, 'msg' => 'Informe o nome do fungicida']);
    exit;
}

try {
    $stmt = $mysqli->prepare("
        INSERT INTO solicitacoes (user_id, tipo, descricao, observacao, status, created_at)
        VALUES (?, 'fungicida', ?, ?, 'pendente', NOW())
    ");
    $stmt->bind_param("iss", $user_id, $nome, $observacao);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => 'Solicitação de fungicida enviada com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar: ' . $e->getMessage()]);
}
