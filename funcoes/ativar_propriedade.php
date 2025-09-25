<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["ok" => false, "error" => "ID inválido"]);
    exit;
}

// Desativa todas
$stmt = $pdo->prepare("UPDATE propriedades SET ativo = 0 WHERE user_id = ?");
$stmt->execute([$user_id]);

// Ativa a selecionada
$stmt = $pdo->prepare("UPDATE propriedades SET ativo = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);

echo json_encode(["ok" => true]);