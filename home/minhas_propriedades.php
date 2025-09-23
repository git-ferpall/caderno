<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$payload = verify_jwt();
$user_id = $payload['sub'] ?? null;

if (!$user_id) {
    die("Usuário não autenticado via token");
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("Usuário não logado");
}

$stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$propriedades = [];
while ($row = $res->fetch_assoc()) {
    $propriedades[] = $row;
}

echo "<h2>Debug propriedades do user_id = {$user_id}</h2>";
echo "<pre>";
print_r($propriedades);
echo "</pre>";
