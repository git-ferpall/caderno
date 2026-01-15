<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_POST['checklist_id'] ?? 0);
if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔒 Confere checklist */
$stmt = $mysqli->prepare("
    SELECT id
    FROM checklists
    WHERE id = ? AND user_id = ? AND concluido = 0
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk) {
    die('Checklist não encontrado ou já finalizado');
}

/* 🔒 Finaliza */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET concluido = 1,
        fechado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$stmt->close();

/* 🔐 Gera hash */
$hash = gerarHashChecklist($mysqli, $checklist_id);

/* 💾 Salva hash */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET hash_documento = ?
    WHERE id = ?
");
$stmt->bind_param("si", $hash, $checklist_id);
$stmt->execute();
$stmt->close();

/* 📄 PDF */
header("Location: ../pdf/gerar.php?id=$checklist_id");
exit;
