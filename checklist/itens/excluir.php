<?php
/**
 * Exclui item de modelo de checklist
 */
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int) $user->sub;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('Item inválido');
}

$stmt = $mysqli->prepare("
    SELECT i.modelo_id, m.criado_por, m.publico
    FROM checklist_modelo_itens i
    INNER JOIN checklist_modelos m ON m.id = i.modelo_id
    WHERE i.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    die('Item não encontrado');
}

if ((int) $item['publico'] === 1) {
    http_response_code(403);
    die('Itens de modelos padrão do sistema não podem ser excluídos');
}

if ((int) $item['criado_por'] !== $user_id) {
    http_response_code(403);
    die('Sem permissão');
}

$stmt = $mysqli->prepare('DELETE FROM checklist_modelo_itens WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: ../modelos/editar.php?id=' . (int) $item['modelo_id']);
exit;
