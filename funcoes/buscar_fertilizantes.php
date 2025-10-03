<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

header('Content-Type: application/json');

$sql = "SELECT id, nome FROM fertilizantes WHERE status = 'ativo' ORDER BY nome ASC";
$result = $mysqli->query($sql);

$fertilizantes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fertilizantes[] = $row;
    }
}

echo json_encode($fertilizantes);
