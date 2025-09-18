<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    exit('Erro ao excluir: ID inválido.');
}

$stmt = $mysqli->prepare("DELETE FROM caderno_produtos WHERE produto_id = ?");
if (!$stmt) {
    exit('Erro na preparação da query: ' . $mysqli->error);
}

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo "sucesso";
} else {
    echo "Erro ao excluir o produto.";
}

$stmt->close();
