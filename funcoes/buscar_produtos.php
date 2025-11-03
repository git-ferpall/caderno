<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

// Garante que a sessão está ativa
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT id, nome
    FROM produtos
    WHERE user_id = ?
    ORDER BY nome ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

echo json_encode($produtos);
