<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../configuracao/login_rate_limit.php';
require_once __DIR__ . '/../../configuracao/senha_policy.php';

[$contaId, $papel, $funcId] = contaRequireGestao();
$actorId = $funcId > 0 ? $funcId : $contaId;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    contaJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$rateScope = 'reset:conta:' . $actorId;
if (login_rate_limit_is_action_blocked($rateScope)) {
    contaJson(['ok' => false, 'msg' => login_rate_limit_action_message()], 429);
}

$targetId = (int)($_POST['user_id'] ?? 0);
$senha    = (string)($_POST['senha'] ?? '');

$erroSenha = senhaValidarPolitica($senha);
if ($erroSenha !== null) {
    contaJson(['ok' => false, 'msg' => $erroSenha], 400);
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

login_rate_limit_record_action($rateScope);

contaJson(['ok' => true, 'msg' => 'Senha redefinida com sucesso.']);
