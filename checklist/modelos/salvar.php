<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/*
 * 🔒 Garante login:
 * - se não estiver logado → redirect
 * - se estiver logado → retorna JWT (claims)
 */
$user = require_login();

/* 👤 ID do usuário autenticado */
$user_id = (int) $user->sub;

/* 📥 Modelo */
$id        = (int)($_POST['id'] ?? 0);
$titulo    = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$publico   = isset($_POST['publico']) ? 1 : 0;

$item_desc   = $_POST['item_desc']   ?? [];
$item_obs    = $_POST['item_obs']    ?? [];
$item_foto   = $_POST['item_foto']   ?? [];
$item_anexo  = $_POST['item_anexo']  ?? [];

$criado_por = $publico ? 0 : $user_id;

if ($titulo === '') {
    die('Título obrigatório');
}

/* ===== MODELO ===== */
if ($id) {

    $stmt = $mysqli->prepare("
        UPDATE checklist_modelos
        SET titulo = ?, descricao = ?, publico = ?, criado_por = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssiii", $titulo, $descricao, $publico, $criado_por, $id);
    $stmt->execute();
    $stmt->close();

    /* Remove itens antigos (vamos recriar todos respeitando a ordem nova) */
    $stmt = $mysqli->prepare("
        DELETE FROM checklist_modelo_itens
        WHERE modelo_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

} else {

    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelos (titulo, descricao, publico, criado_por)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssii", $titulo, $descricao, $publico, $criado_por);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
}

/* ===== ITENS ===== */
$ordem = 1;

$stmt = $mysqli->prepare("
    INSERT INTO checklist_modelo_itens
        (modelo_id, descricao, permite_observacao, permite_foto, permite_anexo, ordem)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($item_desc as $key => $desc) {

    $desc = trim($desc);
    if ($desc === '') continue;

    $permite_obs   = isset($item_obs[$key])   ? 1 : 0;
    $permite_foto  = isset($item_foto[$key])  ? 1 : 0;
    $permite_anexo = isset($item_anexo[$key]) ? 1 : 0;

    /* 🔒 Garantia de exclusividade (backend) */
    if ($permite_foto) {
        $permite_obs = 0;
        $permite_anexo = 0;
    } elseif ($permite_anexo) {
        $permite_obs = 0;
        $permite_foto = 0;
    }

    $stmt->bind_param(
        "isiiii",
        $id,
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

header('Location: index.php');
exit;
