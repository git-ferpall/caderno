<?php
/**
 * Excluir checklist + mídias
 * Retorno SEMPRE em JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 JSON */
$data = json_decode(file_get_contents('php://input'), true);

$checklist_id = (int)($data['id'] ?? 0);

if (!$checklist_id) {
    echo json_encode([
        'ok'   => false,
        'erro' => 'ID inválido'
    ]);
    exit;
}

/* 🔎 Verifica checklist */
$stmt = $mysqli->prepare("
    SELECT id, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk) {
    echo json_encode([
        'ok'   => false,
        'erro' => 'Checklist não encontrado ou sem permissão'
    ]);
    exit;
}

/* 🔥 Remove arquivos físicos */
$basePath = __DIR__ . "/../../uploads/checklists/$checklist_id";

function removerPasta($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = "$dir/$item";
        is_dir($path) ? removerPasta($path) : unlink($path);
    }
    rmdir($dir);
}

removerPasta($basePath);

/* 🔥 Remove arquivos do banco */
$mysqli->query("
    DELETE FROM checklist_item_arquivos
    WHERE checklist_item_id IN (
        SELECT id FROM checklist_itens WHERE checklist_id = $checklist_id
    )
");

/* 🔥 Remove itens */
$stmt = $mysqli->prepare("
    DELETE FROM checklist_itens WHERE checklist_id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$stmt->close();

/* 🔥 Remove checklist */
$stmt = $mysqli->prepare("
    DELETE FROM checklists WHERE id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$stmt->close();

/* ✅ OK */
echo json_encode(['ok' => true]);
exit;
