<?php
/**
 * Exclui um modelo de checklist
 * Stack: MySQLi + Sessão + JWT (SSO)
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

/* 📥 ID do modelo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('Modelo inválido');
}

/* 🔍 Verifica se o modelo existe e pertence ao usuário */
$sql = "
    SELECT id, criado_por, publico
    FROM checklist_modelos
    WHERE id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$modelo = $res->fetch_assoc();
$stmt->close();

if (!$modelo) {
    die('Modelo não encontrado');
}

/*
 * 🔒 Regra de segurança:
 * - modelos públicos NÃO podem ser excluídos
 * - modelo pessoal só pode ser excluído pelo criador
 */
if ($modelo['publico'] == 1) {
    die('Modelos padrão do sistema não podem ser excluídos');
}

if ((int)$modelo['criado_por'] !== (int)$user_id) {
    http_response_code(403);
    die('Você não tem permissão para excluir este modelo');
}

/* 🗑️ Excluir modelo
 * (itens serão excluídos automaticamente se FK estiver com ON DELETE CASCADE)
 */
$sql = "DELETE FROM checklist_modelos WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* 🔁 Volta para lista */
header('Location: index.php');
exit;
