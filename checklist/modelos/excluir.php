<?php
/**
 * Exclui um modelo de checklist
 * Stack: MySQLi + JWT (protect.php)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int) $user->sub;

/* 📥 ID do modelo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('Modelo inválido');
}

/* 🔍 Verifica se o modelo existe */
$sql = "
    SELECT id, criado_por, publico
    FROM checklist_modelos
    WHERE id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$modelo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$modelo) {
    die('Modelo não encontrado');
}

/* 🔒 Regras de segurança */
if ($modelo['publico'] == 1) {
    die('Modelos padrão do sistema não podem ser excluídos');
}

if ((int)$modelo['criado_por'] !== $user_id) {
    http_response_code(403);
    die('Você não tem permissão para excluir este modelo');
}

/* 🗑️ Excluir */
$sql = "DELETE FROM checklist_modelos WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* 🔁 Volta para lista */
header('Location: index.php');
exit;
