<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$contaId] = contaRequireGestao();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    contaJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$targetId = (int)($_POST['user_id'] ?? 0);
$senha    = (string)($_POST['senha'] ?? '');

if (strlen($senha) < 8) {
    contaJson(['ok' => false, 'msg' => 'A senha deve ter pelo menos 8 caracteres.'], 400);
}

$target = $targetId > 0 ? contaBuscarFuncionario($mysqli, $targetId, $contaId) : null;
if (!$target) {
    contaJson(['ok' => false, 'msg' => 'Usuário não encontrado nesta conta.'], 404);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('UPDATE usuarios_caderno SET senha_hash = ? WHERE id = ?');
$stmt->bind_param('si', $hash, $targetId);
$stmt->execute();
$stmt->close();

contaJson(['ok' => true, 'msg' => 'Senha redefinida com sucesso.']);
