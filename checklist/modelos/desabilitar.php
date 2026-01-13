<?php
/**
 * Desabilita (soft delete) um modelo de checklist
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 ID */
$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Modelo inválido');

/* 🔍 Busca modelo */
$stmt = $mysqli->prepare("
    SELECT id, criado_por, publico
    FROM checklist_modelos
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$modelo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$modelo) die('Modelo não encontrado');

/* 🔒 Regras */
if ($modelo['publico'] == 1) {
    die('Modelos padrão não podem ser desabilitados');
}

if ((int)$modelo['criado_por'] !== $user_id) {
    http_response_code(403);
    die('Sem permissão');
}

/* 🚫 Desabilita */
$stmt = $mysqli->prepare("
    UPDATE checklist_modelos
    SET ativo = 0
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* 🔁 Volta */
header('Location: index.php');
exit;
