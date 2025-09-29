<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

// Se precisar filtrar por usuário, use $_SESSION
$user_id = $_SESSION['user_id'] ?? 0;

// Aqui pode ou não filtrar pelo user_id, depende da sua regra de negócio
// Se os produtos forem globais, não precisa do WHERE user_id
$stmt = $mysqli->prepare("SELECT id, nome FROM produtos ORDER BY nome ASC");
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

echo json_encode($produtos);
