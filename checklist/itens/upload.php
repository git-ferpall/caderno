<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

header('Content-Type: application/json');

/* 🔒 Login */
$user = require_login();

/* 📥 Dados */
$checklist_item_id = (int)($_POST['item_id'] ?? 0);
$tipo = $_POST['tipo'] ?? '';
$arquivo = $_FILES['arquivo'] ?? null;

if (!$checklist_item_id || !$arquivo || !in_array($tipo, ['foto','documento'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}

/* 🔎 Busca checklist_id */
$stmt = $mysqli->prepare("
    SELECT checklist_id
    FROM checklist_itens
    WHERE id = ?
");
$stmt->bind_param("i", $checklist_item_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'erro' => 'Item não encontrado']);
    exit;
}

$checklist_id = (int)$res['checklist_id'];

/* 📂 Pasta destino */
$baseDir = __DIR__ . "/../../uploads/checklists/$checklist_id/item_$checklist_item_id";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0775, true);
}

/* 🧪 Validações */
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

if ($tipo === 'foto' && !in_array($ext, ['jpg','jpeg','png','webp'])) {
    echo json_encode(['ok' => false, 'erro' => 'Formato de imagem inválido']);
    exit;
}

if ($tipo === 'documento' && !in_array($ext, ['pdf','doc','docx','xls','xlsx'])) {
    echo json_encode(['ok' => false, 'erro' => 'Documento inválido']);
    exit;
}

/* 🏷️ Nome final */
$nomeFinal = $tipo . '_' . time() . '.' . $ext;
$destino = $baseDir . '/' . $nomeFinal;

if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    echo json_encode(['ok' => false, 'erro' => 'Falha ao salvar arquivo']);
    exit;
}

/* 💾 Salva no banco */
$stmt = $mysqli->prepare("
    INSERT INTO checklist_item_arquivos
        (checklist_item_id, tipo, arquivo)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iss", $checklist_item_id, $tipo, $nomeFinal);
$stmt->execute();
$stmt->close();

echo json_encode([
    'ok' => true,
    'arquivo' => $nomeFinal,
    'tipo' => $tipo
]);
