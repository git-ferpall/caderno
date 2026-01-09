<?php
/**
 * Página inicial do módulo Checklist
 * (MySQLi + SSO + Sessão)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

session_start();

/* 🔐 Recupera user_id (sessão → JWT) */
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}

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
