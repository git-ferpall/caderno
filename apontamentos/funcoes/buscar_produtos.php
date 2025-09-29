<?php
require_once __DIR__ . '/../../configuracao/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, nome FROM produtos ORDER BY nome ASC";
$res = $mysqli->query($sql);

$produtos = [];
while ($row = $res->fetch_assoc()) {
    $produtos[] = [
        "id" => $row["id"],
        "nome" => $row["nome"]
    ];
}

echo json_encode($produtos, JSON_UNESCAPED_UNICODE);
