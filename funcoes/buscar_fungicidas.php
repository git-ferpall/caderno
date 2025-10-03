<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

header('Content-Type: application/json');

$res = $mysqli->query("SELECT id, nome FROM fungicidas WHERE ativo = 1 ORDER BY nome");
$dados = [];
while ($row = $res->fetch_assoc()) {
    $dados[] = $row;
}
echo json_encode($dados);
