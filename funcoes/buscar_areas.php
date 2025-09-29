<?php
require_once __DIR__ . '/../configuracao/conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

$propriedade_id = $_SESSION['propriedade_id'] ?? 0;

$sql = "SELECT id, nome_razao FROM areas WHERE propriedade_id = ? ORDER BY nome_razao ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$propriedade_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
