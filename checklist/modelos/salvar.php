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

/* 📥 Dados */
$id        = $_POST['id'] ?? null;
$titulo    = trim($_POST['titulo']);
$descricao = $_POST['descricao'] ?? '';
$publico   = isset($_POST['publico']) ? 1 : 0;

$item_ids  = $_POST['item_id'] ?? [];
$item_desc = $_POST['item_desc'] ?? [];

$criado_por = $publico ? null : $user_id;

/* 💾 Modelo */
if ($id) {
    $stmt = $mysqli->prepare("
        UPDATE checklist_modelos
        SET titulo=?, descricao=?, publico=?, criado_por=?
        WHERE id=?
    ");
    $stmt->bind_param("ssiii", $titulo, $descricao, $publico, $criado_por, $id);
    $stmt->execute();
    $stmt->close();

    /* limpa itens antigos */
    $stmt = $mysqli->prepare("DELETE FROM checklist_modelo_itens WHERE modelo_id=?");
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

/* 💾 Itens */
$ordem = 1;
$stmt = $mysqli->prepare("
    INSERT INTO checklist_modelo_itens (modelo_id, descricao, ordem)
    VALUES (?, ?, ?)
");

foreach ($item_desc as $desc) {
    if (trim($desc) === '') continue;
    $stmt->bind_param("isi", $id, $desc, $ordem++);
    $stmt->execute();
}
$stmt->close();

header('Location: index.php');
exit;
