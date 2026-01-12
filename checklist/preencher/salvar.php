<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_POST['checklist_id'] ?? 0);
$acao = $_POST['acao'] ?? 'salvar';

if (!$checklist_id) die('Checklist inválido');

/* Checklist */
$stmt = $mysqli->prepare("
    SELECT id, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chk) die('Checklist inválido');

/*
|--------------------------------------------------------------------------
| 💾 SALVAR ITENS (SOMENTE SE NÃO FOR FINALIZAR)
|--------------------------------------------------------------------------
*/
if ($acao === 'salvar') {

    if ((int)$chk['concluido'] === 1) {
        die('Checklist já finalizado');
    }

    $concluidos  = $_POST['concluido'] ?? [];
    $observacoes = $_POST['observacao'] ?? [];

    $stmt = $mysqli->prepare("
        UPDATE checklist_itens
        SET concluido = ?, observacao = ?
        WHERE id = ? AND checklist_id = ?
    ");

    foreach ($observacoes as $item_id => $obs) {

        $item_id = (int)$item_id;
        $done = isset($concluidos[$item_id]) ? 1 : 0;
        $obs = trim($obs);

        $stmt->bind_param(
            "isii",
            $done,
            $obs,
            $item_id,
            $checklist_id
        );
        $stmt->execute();
    }

    $stmt->close();

    header("Location: index.php?id=$checklist_id");
    exit;
}

/*
|--------------------------------------------------------------------------
| 🔒 FINALIZAR (NÃO SALVA ITENS AQUI)
|--------------------------------------------------------------------------
*/
if ($acao === 'finalizar') {

    if ((int)$chk['concluido'] === 1) {
        header("Location: ../pdf/gerar.php?id=$checklist_id");
        exit;
    }

    header("Location: ../fechar/index.php?id=$checklist_id");
    exit;
}
