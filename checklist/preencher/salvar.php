<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_POST['checklist_id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');

/* 🔒 Confere permissão */
$stmt = $mysqli->prepare("
    SELECT id, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk || (int)$chk['concluido'] === 1) {
    die('Checklist inválido ou já finalizado');
}

/* 📥 Dados */
$concluidos = $_POST['concluido'] ?? [];
$observacoes = $_POST['observacao'] ?? [];

/* 💾 Salva itens */
$stmt = $mysqli->prepare("
    UPDATE checklist_itens
    SET
        concluido = ?,
        observacao = ?
    WHERE id = ? AND checklist_id = ?
");

foreach ($observacoes as $item_id => $obs) {
    $done = isset($concluidos[$item_id]) ? 1 : 0;
    $obs = trim($obs);

    $stmt->bind_param("isii", $done, $obs, $item_id, $checklist_id);
    $stmt->execute();
}

$stmt->close();

/* 🔁 Volta */
header('Location: index.php?id=' . $checklist_id);
exit;
