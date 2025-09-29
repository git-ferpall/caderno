<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

// Garante que a sessão tenha user_id
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $mysqli->prepare("
    SELECT id, nome_razao 
    FROM propriedades 
    WHERE user_id = ? AND ativo = 1 
    ORDER BY nome_razao ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$areas = [];
while ($row = $res->fetch_assoc()) {
    $areas[] = $row;
}

echo json_encode($areas);
