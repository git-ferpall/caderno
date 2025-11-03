<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

// Dados do formulário
$nome = trim($_POST['pfnome'] ?? '');
$email = trim($_POST['pfemail'] ?? '');
$tel = trim($_POST['pfnum1'] ?? '');

if ($nome === '' && $email === '' && $tel === '') {
    echo json_encode(['ok' => false, 'msg' => 'Nenhum dado informado.']);
    exit;
}

// Verifica se já existe um contato para o user_id
$stmt = $mysqli->prepare("SELECT id FROM contato_cliente WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$existe = $res->fetch_assoc();
$stmt->close();

if ($existe) {
    $stmt = $mysqli->prepare("
        UPDATE contato_cliente
        SET nome = ?, email = ?, telefone = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $nome, $email, $tel, $user_id);
} else {
    $stmt = $mysqli->prepare("
        INSERT INTO contato_cliente (user_id, nome, email, telefone)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $nome, $email, $tel);
}

$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    'ok' => $ok,
    'msg' => $ok ? 'Dados salvos com sucesso!' : 'Erro ao salvar dados.'
]);
