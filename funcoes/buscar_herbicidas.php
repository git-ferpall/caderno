<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

header('Content-Type: application/json');

$sql = "SELECT id, nome FROM herbicidas WHERE status = 'ativo' ORDER BY nome ASC";
$result = $mysqli->query($sql);

$herbicidas = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $herbicidas[] = $row;
    }
}

echo json_encode($herbicidas);
