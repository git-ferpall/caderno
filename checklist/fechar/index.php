<?php
/**
 * Finalizar checklist
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$checklist_id) {
    die('Checklist inválido');
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
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die('Checklist não encontrado ou sem permissão');
}

if ((int)$checklist['concluido'] === 1) {
    die('Checklist já foi finalizado');
}

/* 🔒 Finaliza checklist */
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

/* 🔁 Redireciona */
header('Location: ../preencher/index.php?id=' . $checklist_id);
exit;
