<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

$stmt = $mysqli->prepare("
    SELECT id, nome 
    FROM produtos 
    ORDER BY nome ASC
");
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

echo json_encode($produtos);
