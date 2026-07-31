<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../configuracao/login_rate_limit.php';
require_once __DIR__ . '/../../configuracao/senha_policy.php';

[$uid, $perfil] = adminRequirePerfil($mysqli, ['admin', 'representante']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    adminJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$rateScope = 'reset:admin:' . $uid;
if (login_rate_limit_is_action_blocked($rateScope)) {
    adminJson(['ok' => false, 'msg' => login_rate_limit_action_message()], 429);
}

$targetId = (int)($_POST['user_id'] ?? 0);
$senha = (string)($_POST['senha'] ?? '');

if ($targetId <= 0) {
    adminJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
}
$erroSenha = senhaValidarPolitica($senha);
if ($erroSenha !== null) {
    adminJson(['ok' => false, 'msg' => $erroSenha], 400);
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

login_rate_limit_record_action($rateScope);

adminJson(['ok' => true, 'msg' => 'Senha redefinida com sucesso.']);
