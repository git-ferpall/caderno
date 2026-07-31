<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../configuracao/usuarios_local.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$payload = verify_jwt();
$user_id = (int)($payload['sub'] ?? 0);
$func_id = (int)($payload['func_id'] ?? 0);

if (!$user_id) {
    echo json_encode([]);
    exit;
}

usuariosEnsureSchema($mysqli);

if ($func_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT nome, email, telefone, aceita_email, aceita_sms, consentimento_contato_em AS consentimento_data
        FROM usuarios_caderno
        WHERE id = ? AND conta_pai IS NOT NULL
        LIMIT 1
    ");
    $stmt->bind_param('i', $func_id);
    $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode($dados ?: []);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT nome, email, telefone, aceita_email, aceita_sms, consentimento_data
    FROM contato_cliente
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode($dados ?: []);
