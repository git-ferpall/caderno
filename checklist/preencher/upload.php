<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

$item_id      = (int)$_POST['item_id'];
$checklist_id = (int)$_POST['checklist_id'];
$tipo         = $_POST['tipo'];
$file         = $_FILES['arquivo'];

if (!$file || $file['error']) {
    echo json_encode(['ok' => false]);
    exit;
}

$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$nome = uniqid() . '.' . $ext;

// 📁 pasta organizada
$dir = __DIR__ . "/../../uploads/checklists/$checklist_id/$item_id";

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

move_uploaded_file($file['tmp_name'], "$dir/$nome");

// salva no banco
$sql = "
INSERT INTO checklist_item_anexos
(checklist_item_id, tipo, arquivo, mime)
VALUES (?, ?, ?, ?)
";
$pdo->prepare($sql)->execute([
    $item_id,
    $tipo,
    $nome,
    $file['type']
]);

echo json_encode([
    'ok' => true,
    'arquivo' => $nome
]);
