<?php
/**
 * Remove arquivo (foto ou documento) de um item de checklist
 * - Remove do disco
 * - Remove do banco
 * - Garante segurança por usuário
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

header('Content-Type: application/json');

/* 🔒 Login */
$user = require_login();
$user_id = (int) $user->sub;

/* 📥 JSON recebido */
$data = json_decode(file_get_contents('php://input'), true);

$item_id = (int)($data['item_id'] ?? 0);
$tipo    = $data['tipo'] ?? '';

if (!$item_id || !in_array($tipo, ['foto', 'documento'])) {
    http_response_code(400);
    echo json_encode([
        'ok'   => false,
        'erro' => 'Dados inválidos'
    ]);
    exit;
}

/* 🔎 Busca arquivo + valida posse */
$stmt = $mysqli->prepare("
    SELECT
        a.id           AS arquivo_id,
        a.arquivo      AS nome_arquivo,
        i.checklist_id AS checklist_id
    FROM checklist_item_arquivos a
    JOIN checklist_itens i   ON i.id = a.checklist_item_id
    JOIN checklists c        ON c.id = i.checklist_id
    WHERE a.checklist_item_id = ?
      AND a.tipo = ?
      AND c.user_id = ?
    LIMIT 1
");
$stmt->bind_param("isi", $item_id, $tipo, $user_id);
$stmt->execute();
$arquivo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$arquivo) {
    http_response_code(404);
    echo json_encode([
        'ok'   => false,
        'erro' => 'Arquivo não encontrado ou sem permissão'
    ]);
    exit;
}

/* 📂 Caminho físico */
$baseDir = __DIR__ . "/../../uploads/checklists/{$arquivo['checklist_id']}/item_{$item_id}";
$caminho = $baseDir . '/' . $arquivo['nome_arquivo'];

/* 🗑️ Remove arquivo físico */
if (is_file($caminho)) {
    unlink($caminho);
}

/* 🧹 Remove registro do banco */
$stmt = $mysqli->prepare("
    DELETE FROM checklist_item_arquivos
    WHERE id = ?
");
$stmt->bind_param("i", $arquivo['arquivo_id']);
$stmt->execute();
$stmt->close();

/* 🧽 Remove pasta se ficar vazia */
if (is_dir($baseDir) && count(glob($baseDir . '/*')) === 0) {
    rmdir($baseDir);
}

echo json_encode([
    'ok' => true
]);
