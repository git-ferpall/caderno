<?php
require_once '../../config/db.php';
session_start();

$anexo_id = (int)$_POST['id'];

// busca anexo + checklist
$sql = "
SELECT a.*, i.checklist_id, c.hash_documento
FROM checklist_item_anexos a
JOIN checklist_itens i ON i.id = a.checklist_item_id
JOIN checklists c ON c.id = i.checklist_id
WHERE a.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$anexo_id]);
$anexo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anexo || $anexo['hash_documento']) {
    echo json_encode(['ok' => false]);
    exit;
}

// remove arquivo
$path = __DIR__ . "/../../uploads/checklists/{$anexo['checklist_id']}/{$anexo['checklist_item_id']}/{$anexo['arquivo']}";
if (file_exists($path)) unlink($path);

// remove banco
$pdo->prepare("DELETE FROM checklist_item_anexos WHERE id = ?")
    ->execute([$anexo_id]);

// histórico
$pdo->prepare("
INSERT INTO checklist_historico
(checklist_id, checklist_item_id, acao, detalhe, user_id)
VALUES (?, ?, 'EXCLUIR_ANEXO', ?, ?)
")->execute([
    $anexo['checklist_id'],
    $anexo['checklist_item_id'],
    $anexo['arquivo'],
    $_SESSION['user_id']
]);

echo json_encode(['ok' => true]);
