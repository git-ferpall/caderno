<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = caderno_require_user_id();
$anexo_id = (int) ($_POST['id'] ?? 0);

if ($anexo_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'id_invalido']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT a.id, a.arquivo, a.checklist_item_id, i.checklist_id, c.user_id, c.hash_documento
    FROM checklist_item_anexos a
    INNER JOIN checklist_itens i ON i.id = a.checklist_item_id
    INNER JOIN checklists c ON c.id = i.checklist_id
    WHERE a.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $anexo_id);
$stmt->execute();
$anexo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$anexo || (int) $anexo['user_id'] !== $user_id || !empty($anexo['hash_documento'])) {
    echo json_encode(['ok' => false, 'err' => 'sem_permissao']);
    exit;
}

$path = __DIR__ . "/../../uploads/checklists/{$anexo['checklist_id']}/{$anexo['checklist_item_id']}/{$anexo['arquivo']}";
if (is_file($path)) {
    unlink($path);
}

$stmt = $mysqli->prepare('DELETE FROM checklist_item_anexos WHERE id = ?');
$stmt->bind_param('i', $anexo_id);
$stmt->execute();
$stmt->close();

$stmt = $mysqli->prepare("
    INSERT INTO checklist_historico
    (checklist_id, checklist_item_id, acao, detalhe, user_id)
    VALUES (?, ?, 'EXCLUIR_ANEXO', ?, ?)
");
$checklistId = (int) $anexo['checklist_id'];
$itemId = (int) $anexo['checklist_item_id'];
$detalhe = (string) $anexo['arquivo'];
$stmt->bind_param('iisi', $checklistId, $itemId, $detalhe, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true]);
