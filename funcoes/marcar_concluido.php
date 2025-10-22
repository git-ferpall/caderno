<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {
    // Atualiza o status para "concluido"
    $stmt = $mysqli->prepare("UPDATE apontamentos SET status = 'concluido', data_conclusao = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true, 'msg' => 'Manejo marcado como concluído']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Nenhum registro atualizado (ID pode estar incorreto)']);
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro no servidor: ' . $e->getMessage()]);
}
