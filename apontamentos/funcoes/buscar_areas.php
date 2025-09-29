<?php
require_once __DIR__ . '/../../configuracao/conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

header('Content-Type: application/json; charset=utf-8');

// pega a propriedade ativa
$user_id = $_SESSION['user_id'] ?? 0;

$sql = "SELECT id, nome_razao 
          FROM propriedades 
         WHERE user_id = ? AND ativo = 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$areas = [];
while ($row = $res->fetch_assoc()) {
    $areas[] = [
        "id" => $row["id"],
        "nome_razao" => $row["nome_razao"]
    ];
}

echo json_encode($areas, JSON_UNESCAPED_UNICODE);
