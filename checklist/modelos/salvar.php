<?php
require_once '../../config/db.php';
session_start();

$user_id = $_SESSION['user_id'];

$id        = $_POST['id'] ?? null;
$titulo    = $_POST['titulo'];
$descricao = $_POST['descricao'] ?? null;
$publico   = isset($_POST['publico']) ? 1 : 0;

$criado_por = $publico ? null : $user_id;

if ($id) {
    $sql = "
        UPDATE checklist_modelos
        SET titulo = ?, descricao = ?, publico = ?, criado_por = ?
        WHERE id = ?
    ";
    $pdo->prepare($sql)->execute([$titulo, $descricao, $publico, $criado_por, $id]);
} else {
    $sql = "
        INSERT INTO checklist_modelos (titulo, descricao, publico, criado_por)
        VALUES (?, ?, ?, ?)
    ";
    $pdo->prepare($sql)->execute([$titulo, $descricao, $publico, $criado_por]);
}

header('Location: index.php');
