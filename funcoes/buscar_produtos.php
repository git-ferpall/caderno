<?php
require_once __DIR__ . '/../configuracao/conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

$sql = "SELECT id, nome FROM produtos ORDER BY nome ASC";
$stmt = $pdo->query($sql);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
