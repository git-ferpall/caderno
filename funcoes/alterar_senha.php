<?php
declare(strict_types=1);

/**
 * Troca de senha do próprio usuário local (exige a senha atual).
 */

require_once __DIR__ . '/../configuracao/usuarios_local.php'; // conexão + helpers
require_once __DIR__ . '/../configuracao/login_rate_limit.php';
require_once __DIR__ . '/../configuracao/senha_policy.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

function senhaJson(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    senhaJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$payload = verify_jwt();

// Funcionário de conta troca a PRÓPRIA senha (func_id), nunca a da conta principal
$funcId = (int)($payload['func_id'] ?? 0);
$userId = $funcId > 0 ? $funcId : (int)($payload['sub'] ?? 0);

if ($funcId <= 0 && ($payload['tipo'] ?? '') !== 'local') {
    senhaJson(['ok' => false, 'msg' => 'A senha de usuários Frutag é gerenciada pela Frutag.'], 400);
}
if (!empty($payload['imp_by'])) {
    senhaJson(['ok' => false, 'msg' => 'Não é possível trocar a senha enquanto acessa o perfil de outro usuário.'], 403);
}

$senhaAtual = (string)($_POST['senha_atual'] ?? '');
$senhaNova  = (string)($_POST['senha_nova'] ?? '');

if ($senhaAtual === '' || $senhaNova === '') {
    senhaJson(['ok' => false, 'msg' => 'Preencha a senha atual e a nova senha.'], 400);
}
$erroSenha = senhaValidarPolitica($senhaNova);
if ($erroSenha !== null) {
    senhaJson(['ok' => false, 'msg' => $erroSenha], 400);
}

$rateScope = 'pwd:' . $userId;
if (login_rate_limit_is_blocked($rateScope)) {
    senhaJson(['ok' => false, 'msg' => login_rate_limit_block_message($rateScope)], 429);
}

$usuario = usuarioBuscarPorId($mysqli, $userId);
if (!$usuario || $usuario['origem'] !== 'local' || (int)$usuario['ativo'] !== 1) {
    senhaJson(['ok' => false, 'msg' => 'Usuário não encontrado.'], 404);
}
if (!password_verify($senhaAtual, (string)$usuario['senha_hash'])) {
    login_rate_limit_record_failure($rateScope);
    senhaJson(['ok' => false, 'msg' => 'Senha atual incorreta.'], 400);
}

$hash = password_hash($senhaNova, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('UPDATE usuarios_caderno SET senha_hash = ? WHERE id = ?');
$stmt->bind_param('si', $hash, $userId);
$stmt->execute();
$stmt->close();

login_rate_limit_reset($rateScope);
senhaJson(['ok' => true, 'msg' => 'Senha alterada com sucesso.']);
