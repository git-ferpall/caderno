<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();

/* 📥 Dados */
$id         = (int)($_POST['id'] ?? 0);
$modelo_id  = (int)($_POST['modelo_id'] ?? 0);
$descricao  = trim($_POST['descricao'] ?? '');

$permite_observacao = isset($_POST['permite_observacao']) ? 1 : 0;
$permite_foto       = isset($_POST['permite_foto']) ? 1 : 0;
$permite_anexo      = isset($_POST['permite_anexo']) ? 1 : 0;

if (!$modelo_id || $descricao === '') {
    die('Dados inválidos');
}

if ($id) {
    /* ✏️ Atualizar */
    $stmt = $mysqli->prepare("
        UPDATE checklist_modelo_itens
        SET
            descricao = ?,
            permite_observacao = ?,
            permite_foto = ?,
            permite_anexo = ?
        WHERE id = ? AND modelo_id = ?
    ");
    $stmt->bind_param(
        "siiiii",
        $descricao,
        $permite_observacao,
        $permite_foto,
        $permite_anexo,
        $id,
        $modelo_id
    );
} else {
    /* ➕ Inserir */
    $stmt = $mysqli->prepare("
        INSERT INTO checklist_modelo_itens
        (modelo_id, descricao, permite_observacao, permite_foto, permite_anexo, ordem)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->bind_param(
        "isiii",
        $modelo_id,
        $descricao,
        $permite_observacao,
        $permite_foto,
        $permite_anexo
    );
}

$stmt->execute();
$stmt->close();

/* 🔁 Volta para a lista */
header('Location: index.php?modelo_id=' . $modelo_id);
exit;
