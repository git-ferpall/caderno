<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../configuracao/usuarios_local.php'; // conexão ($mysqli) + helpers
require_once __DIR__ . '/../../sso/verify_jwt.php';

// Cookie que preserva o token original do admin durante a impersonação
const IMPERSONATE_COOKIE = 'token_admin';

function adminJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** JWT bruto da requisição atual (mesma ordem de prioridade do verify_jwt). */
function adminRawJwt(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) return $m[1];
    if (!empty($_COOKIE['AUTH_COOKIE'])) return $_COOKIE['AUTH_COOKIE'];
    if (!empty($_COOKIE['token'])) return $_COOKIE['token'];
    return null;
}

/** Autentica e retorna [id, perfil, payload]. Sai com 401/403 em falha. */
function adminAuth(mysqli $mysqli): array
{
    $payload = verify_jwt(); // sai com 401 se token inválido
    $id = (int)($payload['sub'] ?? 0);
    if ($id <= 0) {
        adminJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
    }
    $reg = usuarioBuscarPorId($mysqli, $id);
    if ($reg && (int)$reg['ativo'] !== 1) {
        adminJson(['ok' => false, 'msg' => 'Usuário desativado.'], 403);
    }
    $perfil = $reg['perfil'] ?? 'usuario'; // Frutag não provisionado = usuário comum
    return [$id, $perfil, $payload];
}

/** Exige um dos perfis informados e bloqueia ações administrativas durante impersonação. */
function adminRequirePerfil(mysqli $mysqli, array $perfis): array
{
    [$id, $perfil, $payload] = adminAuth($mysqli);
    if (!empty($payload['imp_by'])) {
        adminJson(['ok' => false, 'msg' => 'Ação indisponível enquanto estiver acessando o perfil de outro usuário.'], 403);
    }
    if (!in_array($perfil, $perfis, true)) {
        adminJson(['ok' => false, 'msg' => 'Acesso negado.'], 403);
    }
    return [$id, $perfil, $payload];
}
