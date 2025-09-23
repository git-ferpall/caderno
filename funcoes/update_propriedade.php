<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

header('Content-Type: application/json; charset=utf-8');

// captura dados
$id      = (int)($_POST['id'] ?? 0);
$nome    = trim($_POST['pfrazao'] ?? '');

if (!$id || $nome === '') {
    echo json_encode(['ok' => false, 'err' => 'missing_fields']);
    exit;
}

try {
    $stmt = $mysqli->prepare("UPDATE propriedades SET nome_razao = ? WHERE id = ?");
    $stmt->bind_param("si", $nome, $id);
    $stmt->execute();

    echo json_encode(['ok' => true, 'updated' => $stmt->affected_rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db', 'msg' => $e->getMessage()]);
}
