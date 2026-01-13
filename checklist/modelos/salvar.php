<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Dados do modelo */
$modelo_id  = (int)($_POST['id'] ?? 0);
$titulo     = trim($_POST['titulo'] ?? '');
$descricao  = trim($_POST['descricao'] ?? '');
$publico    = isset($_POST['publico']) ? 1 : 0;

$item_keys  = $_POST['item_key']   ?? [];
$item_desc  = $_POST['item_desc']  ?? [];
$item_obs   = $_POST['item_obs']   ?? [];
$item_foto  = $_POST['item_foto']  ?? [];
$item_anexo = $_POST['item_anexo'] ?? [];

if ($titulo === '') {
    die('Título obrigatório');
}

/* 🔐 Criador */
$criado_por = $publico ? 0 : $user_id;

/* =========================
 * MODELO
 * ========================= */
if ($modelo_id > 0) {

    /* Atualiza */
    $stmt = $mysqli->prepare("
        UPDATE checklist_modelos
        SET titulo = ?, descricao = ?, publico = ?, criado_por = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssiii", $titulo, $descricao, $publico, $criado_por, $modelo_id);
    $stmt->execute();
    $stmt->close();

    /* Remove itens antigos */
    $stmt = $mysqli->prepare("
        DELETE FROM checklist_modelo_itens
        WHERE modelo_id = ?
    ");
    $stmt->bind_param("i", $modelo_id);
    $stmt->execute();
    $stmt->close();

} else {

    /* Insere */
    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelos
            (titulo, descricao, publico, criado_por)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssii", $titulo, $descricao, $publico, $criado_por);
    $stmt->execute();
    $modelo_id = $stmt->insert_id;
    $stmt->close();
}

/* =========================
 * ITENS
 * ========================= */
$ordem = 1;

$stmt = $mysqli->prepare("
    INSERT INTO checklist_modelo_itens
        (modelo_id, descricao, permite_observacao, permite_foto, permite_anexo, ordem)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($item_keys as $key) {

    $desc = trim($item_desc[$key] ?? '');
    if ($desc === '') continue;

    $permite_obs   = isset($item_obs[$key])   ? 1 : 0;
    $permite_foto  = isset($item_foto[$key])  ? 1 : 0;
    $permite_anexo = isset($item_anexo[$key]) ? 1 : 0;

    $stmt->bind_param(
        "isiiii",
        $modelo_id,
        $desc,
        $permite_obs,
        $permite_foto,
        $permite_anexo,
        $ordem
    );

    $stmt->execute();
    $ordem++;
}

$stmt->close();

/* 🔁 Retorno */
header('Location: index.php');
exit;
