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

if (empty($_FILES['audio']['tmp_name'])) {
    iaJson(['ok' => false, 'err' => 'Envie um arquivo de áudio.'], 400);
}

$tmp = $_FILES['audio']['tmp_name'];
$mime = $_FILES['audio']['type'] ?: 'audio/webm';
$size = (int) ($_FILES['audio']['size'] ?? 0);

if ($size <= 0 || $size > 25 * 1024 * 1024) {
    iaJson(['ok' => false, 'err' => 'Áudio inválido ou muito grande (máx. 25 MB).'], 400);
}

try {
    $transcricao = iaTranscreverAudio($tmp, $mime);
    $contexto = iaContextoUsuario($mysqli, $user_id);
    $intent = iaInterpretarComando($transcricao, $contexto);
    $resolucao = iaResolverEntidades($intent, $contexto);
    $resumo = iaResumoIntent($intent, $resolucao, $contexto);
    $precisaConfirmacao = iaPrecisaConfirmacao($intent, $resolucao);

    $executor = new ApontamentoExecutor($mysqli, $user_id);
    $resultado = null;

    if (!$precisaConfirmacao && $intent['acao'] !== 'desconhecido') {
        $resultado = $executor->executar($intent, $resolucao);
        if (!($resultado['ok'] ?? false)) {
            $precisaConfirmacao = true;
        }
    }

    iaJson([
        'ok' => true,
        'transcricao' => $transcricao,
        'intent' => $intent,
        'resolucao' => $resolucao,
        'resumo' => $resumo,
        'precisa_confirmacao' => $precisaConfirmacao,
        'executado' => (bool) ($resultado['executado'] ?? false),
        'msg' => $resultado['msg'] ?? ($intent['mensagem'] ?? $resumo),
        'resultado' => $resultado,
    ]);
} catch (Throwable $e) {
    iaJson(['ok' => false, 'err' => $e->getMessage()], 500);
}
