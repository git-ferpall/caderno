<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

$data = json_decode(file_get_contents('php://input'), true);

$campos = [];
$valores = [];

if (isset($data['concluido'])) {
    $campos[] = 'concluido = ?';
    $valores[] = $data['concluido'];
    $campos[] = 'data_conclusao = ?';
    $valores[] = $data['concluido'] ? date('Y-m-d H:i:s') : null;
}

if (isset($data['observacao'])) {
    $campos[] = 'observacao = ?';
    $valores[] = $data['observacao'];
}

$valores[] = $data['id'];

$sql = "UPDATE checklist_itens SET ".implode(',', $campos)." WHERE id = ?";
$pdo->prepare($sql)->execute($valores);

echo json_encode(['ok' => true]);
