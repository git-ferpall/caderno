<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

[$uid, $perfil] = adminRequirePerfil($mysqli, ['admin', 'representante']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    adminJson(['ok' => false, 'msg' => 'Método inválido.'], 405);
}

$targetId = (int)($_POST['user_id'] ?? 0);
if ($targetId <= 0) {
    adminJson(['ok' => false, 'msg' => 'user_id inválido.'], 400);
}
if ($targetId === $uid) {
    adminJson(['ok' => false, 'msg' => 'Você já está no seu próprio perfil.'], 400);
}

$target = usuarioBuscarPorId($mysqli, $targetId);
if (!$target && $perfil === 'admin') {
    $target = usuarioGarantirFrutag($mysqli, $targetId); // Frutag legado
}
if (!$target) {
    adminJson(['ok' => false, 'msg' => 'Usuário não encontrado.'], 404);
}
if ((int)$target['ativo'] !== 1) {
    adminJson(['ok' => false, 'msg' => 'Usuário desativado.'], 400);
}
if ($perfil !== 'admin' && (int)$target['criado_por'] !== $uid) {
    adminJson(['ok' => false, 'msg' => 'Você só pode acessar clientes cadastrados por você.'], 403);
}

$tokenOriginal = adminRawJwt();
if (!$tokenOriginal) {
    adminJson(['ok' => false, 'msg' => 'Sessão inválida.'], 401);
}

$tipo = $target['origem'] === 'local' ? 'local' : ($target['tipo_frutag'] ?: 'cliente');
$jwt = usuarioEmitirJwt([
    'sub'    => (int)$target['id'],
    'tipo'   => $tipo,
    'name'   => $target['nome'],
    'email'  => $target['email'],
    'perfil' => $target['perfil'],
    'imp_by' => $uid, // marca a sessão como impersonada
]);

// Preserva o token do admin e assume a identidade do usuário-alvo
setcookie(IMPERSONATE_COOKIE, $tokenOriginal, usuarioCookieOptions(3600));
setcookie(AUTH_COOKIE, $jwt, usuarioCookieOptions(3600));

// O cookie literal 'AUTH_COOKIE' (sso_autologin) tem prioridade no verify_jwt;
// remove para a impersonação valer imediatamente.
if (!empty($_COOKIE['AUTH_COOKIE'])) {
    setcookie('AUTH_COOKIE', '', usuarioCookieOptions(0));
}

adminJson(['ok' => true, 'msg' => 'Acessando como ' . ($target['nome'] ?: ('usuário #' . $target['id'])) . '.', 'redirect' => '/home/']);
