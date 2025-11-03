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
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT nome, email, telefone, aceita_email, aceita_sms, consentimento_data
    FROM contato_cliente
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$dados = $res->fetch_assoc();
$stmt->close();

echo json_encode($dados ?: []);
