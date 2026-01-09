<?php
require_once '../../config/db.php';

$dados = json_decode(file_get_contents('php://input'), true);

$sql = "UPDATE checklist_modelo_itens SET ordem = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);

foreach ($dados as $item) {
    $stmt->execute([$item['ordem'], $item['id']]);
}

echo json_encode(['status' => 'ok']);
