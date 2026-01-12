<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔐 Garante que é do usuário e ainda não foi fechado */
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

/* 1️⃣ FINALIZA CHECKLIST */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET concluido = 1,
        fechado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$stmt->close();

/* 2️⃣ GERA HASH (agora SIM) */
$hash = gerarHashChecklist($mysqli, $checklist_id);

/* 3️⃣ SALVA HASH */
$stmt = $mysqli->prepare("
    UPDATE checklists
    SET hash_documento = ?
    WHERE id = ?
");
$stmt->bind_param("si", $hash, $checklist_id);
$stmt->execute();
$stmt->close();

/* 4️⃣ REDIRECIONA PARA PDF */
header("Location: ../pdf/gerar.php?id=$checklist_id");
exit;
