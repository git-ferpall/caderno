<?php
/**
 * Salvar preenchimento de checklist
 * Stack: MySQLi + protect.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Dados principais */
$checklist_id = (int)($_POST['checklist_id'] ?? 0);
$acao = $_POST['acao'] ?? 'salvar';

if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔒 Confere checklist do usuário */
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
    die('Checklist não encontrado ou sem permissão');
}

if ((int)$chk['concluido'] === 1) {
    die('Checklist já finalizado');
}

/* 📥 Dados enviados */
$concluidos   = $_POST['concluido']   ?? [];
$observacoes = $_POST['observacao']  ?? [];
$datas       = $_POST['data']         ?? [];
$multipla    = $_POST['multipla']     ?? [];

/* 🔎 Busca TODOS os itens do checklist */
$stmt = $mysqli->prepare("
    SELECT id
    FROM checklist_itens
    WHERE checklist_id = ?
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 💾 Atualiza itens */
$stmt = $mysqli->prepare("
    UPDATE checklist_itens
    SET
        concluido = ?,
        observacao = ?,
        valor_data = ?,
        valor_multipla = ?
    WHERE id = ? AND checklist_id = ?
");

foreach ($itens as $item) {

    $item_id = (int)$item['id'];

    /* ✔ Concluído */
    $done = isset($concluidos[$item_id]) ? 1 : 0;

    /* 📝 Observação */
    $obs = trim($observacoes[$item_id] ?? '');
    $obs = $obs !== '' ? $obs : null;

    /* 📅 Data */
    $data = $datas[$item_id] ?? null;
    if ($data === '') {
        $data = null;
    }

    /* 🔢 Múltipla escolha (JSON) */
    if (isset($multipla[$item_id])) {

        $valor = $multipla[$item_id];

        if (is_array($valor)) {
            $multi = json_encode($valor, JSON_UNESCAPED_UNICODE);
        } else {
            // radio → string
            $multi = json_encode([$valor], JSON_UNESCAPED_UNICODE);
        }

    } else {
        $multi = null;
    }

    $stmt->bind_param(
        "isssii",
        $done,
        $obs,
        $data,
        $multi,
        $item_id,
        $checklist_id
    );

    $stmt->execute();
}

$stmt->close();

/* 🔒 Finalizar checklist */
if ($acao === 'finalizar') {

    $stmt = $mysqli->prepare("
        UPDATE checklists
        SET concluido = 1
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $checklist_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../fechar/assinar.php?id=$checklist_id");
    exit;
}

/* 🔁 Apenas salvar */
header("Location: index.php?id=$checklist_id");
exit;
