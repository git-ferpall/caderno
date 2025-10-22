<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE apontamentos SET status = 'concluido', data_conclusao = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();

echo json_encode(['ok' => $ok]);
