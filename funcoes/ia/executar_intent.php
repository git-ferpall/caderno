<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/ia_helpers.php';
require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/ApontamentoExecutor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    iaJson(['ok' => false, 'err' => 'Método não permitido'], 405);
}

$user_id = iaAuthUserId();

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    iaJson(['ok' => false, 'err' => 'JSON inválido'], 400);
}

$intent = $payload['intent'] ?? null;
if (!is_array($intent)) {
    iaJson(['ok' => false, 'err' => 'Intent ausente'], 400);
}

$intent = iaNormalizarIntent($intent);
$contexto = iaContextoUsuario($mysqli, $user_id);
$resolucao = iaResolverEntidades($intent, $contexto);

try {
    $executor = new ApontamentoExecutor($mysqli, $user_id);
    $resultado = $executor->executar($intent, $resolucao);

    iaJson([
        'ok' => (bool) ($resultado['ok'] ?? false),
        'executado' => (bool) ($resultado['executado'] ?? false),
        'msg' => $resultado['msg'] ?? 'Operação concluída.',
        'resultado' => $resultado,
        'resumo' => iaResumoIntent($intent, $resolucao, $contexto),
    ]);
} catch (Throwable $e) {
    iaJson(['ok' => false, 'err' => $e->getMessage()], 500);
}
