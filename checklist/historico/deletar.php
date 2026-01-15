<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('ID inválido');

/* 🔎 Checklist */
$stmt = $mysqli->prepare("
    SELECT c.titulo, m.publico
    FROM checklists c
    JOIN checklist_modelos m ON m.id = c.modelo_id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk) die('Checklist não encontrado');
if ((int)$chk['publico'] === 1) die('Modelo público não pode ser excluído');

/* 🧾 Auditoria */
$stmt = $mysqli->prepare("
    INSERT INTO checklist_auditoria
        (checklist_id, usuario_id, acao, ip, user_agent, dados_json)
    VALUES (?, ?, 'delete', ?, ?, ?)
");
$stmt->bind_param(
    "iisss",
    $checklist_id,
    $user_id,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT'],
    json_encode(['titulo'=>$chk['titulo']], JSON_UNESCAPED_UNICODE)
);
$stmt->execute();
$stmt->close();

/* 🧹 Remove mídia */
$dir = __DIR__ . "/../../uploads/checklists/$checklist_id";
function apagarDir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = "$dir/$f";
        is_dir($p) ? apagarDir($p) : unlink($p);
    }
    rmdir($dir);
}
apagarDir($dir);

/* 🗑 Exclui banco */
$mysqli->query("DELETE FROM checklist_item_arquivos WHERE checklist_item_id IN (
    SELECT id FROM checklist_itens WHERE checklist_id = $checklist_id
)");
$mysqli->query("DELETE FROM checklist_itens WHERE checklist_id = $checklist_id");
$mysqli->query("DELETE FROM checklists WHERE id = $checklist_id");

header('Location: index.php');
exit;
