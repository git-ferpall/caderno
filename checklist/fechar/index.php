<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$checklist_id) die('Checklist inválido');

/* 🔎 Verifica checklist */
$stmt = $mysqli->prepare("
    SELECT id, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) die('Checklist não encontrado');
if ((int)$checklist['concluido'] === 1) die('Checklist já finalizado');

/* =========================
 * 🔒 FECHAMENTO
 * ========================= */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET
        concluido = 1,
        fechado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$stmt->close();

/* =========================
 * 🔐 GERA HASH DE INTEGRIDADE
 * ========================= */
$hash = gerarHashChecklist($mysqli, $checklist_id);

$stmt = $mysqli->prepare("
    UPDATE checklists
    SET hash_documento = ?
    WHERE id = ?
");
$stmt->bind_param("si", $hash, $checklist_id);
$stmt->execute();
$stmt->close();

/* =========================
 * 🔁 REDIRECIONA
 * ========================= */
header('Location: ../preencher/index.php?id=' . $checklist_id);
exit;
