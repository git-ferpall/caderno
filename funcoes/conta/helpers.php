<?php
declare(strict_types=1);

/**
 * Helpers dos endpoints de gestão de usuários da conta (funcionários).
 * Podem gerenciar: o dono da conta e funcionários com papel_conta = 'admin'.
 */

require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../configuracao/usuarios_local.php'; // conexão ($mysqli) + helpers
require_once __DIR__ . '/../../sso/verify_jwt.php';

function contaJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Autentica e retorna [contaId, papel, funcId, payload].
 * papel: 'dono' | 'admin' | 'apontador' (verify_jwt já revalidou o funcionário).
 */
function contaAuth(): array
{
    $payload = verify_jwt();
    $contaId = (int)($payload['sub'] ?? 0);
    if ($contaId <= 0) {
        contaJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
    }
    $funcId = (int)($payload['func_id'] ?? 0);
    $papel  = $funcId > 0 ? (string)($payload['func_papel'] ?? 'apontador') : 'dono';
    return [$contaId, $papel, $funcId, $payload];
}

/** Exige permissão de gestão da conta (dono ou funcionário admin). */
function contaRequireGestao(): array
{
    [$contaId, $papel, $funcId, $payload] = contaAuth();
    if (!in_array($papel, ['dono', 'admin'], true)) {
        contaJson(['ok' => false, 'msg' => 'Seu acesso permite apenas registrar apontamentos.'], 403);
    }
    return [$contaId, $papel, $funcId, $payload];
}

/** Busca um funcionário garantindo que pertence à conta. */
function contaBuscarFuncionario(mysqli $mysqli, int $funcionarioId, int $contaId): ?array
{
    $stmt = $mysqli->prepare("SELECT * FROM usuarios_caderno WHERE id = ? AND conta_pai = ? LIMIT 1");
    $stmt->bind_param('ii', $funcionarioId, $contaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
