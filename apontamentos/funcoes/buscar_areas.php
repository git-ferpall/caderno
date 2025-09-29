<?php
require_once __DIR__ . '/../../configuracao/conexao.php';
$propriedade_id = $_SESSION['propriedade_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, nome_razao FROM propriedades WHERE ativo=1 AND id=?");
$stmt->execute([$propriedade_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
