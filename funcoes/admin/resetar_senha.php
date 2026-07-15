<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$uid, $perfil] = adminRequirePerfil($mysqli, ['admin', 'representante']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    adminJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$targetId = (int)($_POST['user_id'] ?? 0);
$senha = (string)($_POST['senha'] ?? '');

if ($targetId <= 0) {
    adminJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
}
if (strlen($senha) < 8) {
    adminJson(['ok' => false, 'msg' => 'A nova senha deve ter pelo menos 8 caracteres.'], 400);
}

$target = usuarioBuscarPorId($mysqli, $targetId);
if (!$target) {
    adminJson(['ok' => false, 'msg' => 'Usuário não encontrado.'], 404);
}
if ($target['origem'] !== 'local') {
    adminJson(['ok' => false, 'msg' => 'A senha de usuários Frutag é gerenciada pela Frutag.'], 400);
}
if ($perfil !== 'admin' && (int)$target['criado_por'] !== $uid) {
    adminJson(['ok' => false, 'msg' => 'Você só pode alterar clientes cadastrados por você.'], 403);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('UPDATE usuarios_caderno SET senha_hash = ? WHERE id = ?');
$stmt->bind_param('si', $hash, $targetId);
$stmt->execute();
$stmt->close();

adminJson(['ok' => true, 'msg' => 'Senha redefinida com sucesso.']);
