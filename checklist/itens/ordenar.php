<?php
/**
 * Reordena itens de modelo de checklist
 */
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

header('Content-Type: application/json; charset=utf-8');

$user = require_login();
$user_id = (int) $user->sub;

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados) || $dados === []) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'msg' => 'Dados inválidos']);
    exit;
}

$ids = [];
foreach ($dados as $item) {
    $itemId = (int) ($item['id'] ?? 0);
    if ($itemId > 0) {
        $ids[] = $itemId;
    }
}

if ($ids === []) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'msg' => 'Nenhum item válido']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $mysqli->prepare("
    SELECT i.id, m.criado_por, m.publico
    FROM checklist_modelo_itens i
    INNER JOIN checklist_modelos m ON m.id = i.modelo_id
    WHERE i.id IN ($placeholders)
");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($rows) !== count($ids)) {
    http_response_code(404);
    echo json_encode(['status' => 'erro', 'msg' => 'Item não encontrado']);
    exit;
}

foreach ($rows as $row) {
    if ((int) $row['publico'] === 1 || (int) $row['criado_por'] !== $user_id) {
        http_response_code(403);
        echo json_encode(['status' => 'erro', 'msg' => 'Sem permissão']);
        exit;
    }
}

$stmt = $mysqli->prepare('UPDATE checklist_modelo_itens SET ordem = ? WHERE id = ?');
foreach ($dados as $item) {
    $ordem = (int) ($item['ordem'] ?? 0);
    $itemId = (int) ($item['id'] ?? 0);
    if ($itemId <= 0) {
        continue;
    }
    $stmt->bind_param('ii', $ordem, $itemId);
    $stmt->execute();
}
$stmt->close();

echo json_encode(['status' => 'ok']);
