<?php
declare(strict_types=1);

require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/ApontamentoExecutor.php';

/**
 * Pipeline único: áudio ou texto → intent → executor (app web + WhatsApp).
 */
final class IaPipeline
{
    public function __construct(
        private mysqli $mysqli,
        private int $user_id
    ) {}

    public function processFromAudio(string $filePath, string $mime = 'audio/ogg'): array
    {
        $transcricao = iaTranscreverAudio($filePath, $mime);
        return $this->processFromText($transcricao, $transcricao);
    }

    public function processFromText(string $texto, ?string $transcricao = null): array
    {
        $contexto = iaContextoUsuario($this->mysqli, $this->user_id);
        $intent = iaInterpretarComando($texto, $contexto);
        $resolucao = iaResolverEntidades($intent, $contexto);
        $resumo = iaResumoIntent($intent, $resolucao, $contexto);
        $precisaConfirmacao = iaPrecisaConfirmacao($intent, $resolucao);

        $resultado = null;
        if (!$precisaConfirmacao && ($intent['acao'] ?? '') !== 'desconhecido') {
            $resultado = $this->executeIntent($intent, $resolucao);
            if (!($resultado['ok'] ?? false)) {
                $precisaConfirmacao = true;
            }
        }

        return [
            'ok' => true,
            'transcricao' => $transcricao ?? $texto,
            'intent' => $intent,
            'resolucao' => $resolucao,
            'resumo' => $resumo,
            'precisa_confirmacao' => $precisaConfirmacao,
            'executado' => (bool) ($resultado['executado'] ?? false),
            'msg' => $resultado['msg'] ?? ($intent['mensagem'] ?? $resumo),
            'resultado' => $resultado,
        ];
    }

    public function executeIntent(array $intent, ?array $resolucao = null): array
    {
        $intent = iaNormalizarIntent($intent);
        if ($resolucao === null) {
            $contexto = iaContextoUsuario($this->mysqli, $this->user_id);
            $resolucao = iaResolverEntidades($intent, $contexto);
        }

        $executor = new ApontamentoExecutor($this->mysqli, $this->user_id);
        return $executor->executar($intent, $resolucao);
    }
}
