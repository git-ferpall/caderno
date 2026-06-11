<?php
declare(strict_types=1);

require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/dialogo.php';
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

    public function processFromAudio(
        string $filePath,
        string $mime = 'audio/ogg',
        ?array $intentParcial = null,
        ?string $campoDialogo = null
    ): array {
        $transcricao = iaTranscreverAudio($filePath, $mime);
        return $this->processFromText($transcricao, $transcricao, $intentParcial, $campoDialogo);
    }

    public function processFromText(
        string $texto,
        ?string $transcricao = null,
        ?array $intentParcial = null,
        ?string $campoDialogo = null
    ): array {
        $contexto = iaContextoUsuario($this->mysqli, $this->user_id);

        if ($intentParcial !== null && $campoDialogo !== null && $campoDialogo !== '') {
            $intent = iaMesclarRespostaDialogo($intentParcial, $campoDialogo, $texto, $contexto);
        } else {
            $intent = iaInterpretarComando($texto, $contexto);
        }

        return $this->finalizarIntent($intent, $transcricao ?? $texto, $contexto);
    }

    private function finalizarIntent(array $intent, string $transcricao, array $contexto): array
    {
        $resolucao = iaResolverEntidades($intent, $contexto);
        $resumo = iaResumoIntent($intent, $resolucao, $contexto);
        $perguntaDialogo = iaProximaPergunta($intent, $resolucao, $contexto);

        if ($perguntaDialogo !== null && iaDeveDialogar($intent, $resolucao, $contexto)) {
            return [
                'ok' => true,
                'transcricao' => $transcricao,
                'intent' => $intent,
                'resolucao' => $resolucao,
                'resumo' => $resumo,
                'precisa_dialogo' => true,
                'precisa_confirmacao' => false,
                'pergunta' => $perguntaDialogo['pergunta'],
                'campo_dialogo' => $perguntaDialogo['campo'],
                'intent_parcial' => $intent,
                'executado' => false,
                'msg' => $perguntaDialogo['pergunta'],
                'resultado' => null,
            ];
        }

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
            'transcricao' => $transcricao,
            'intent' => $intent,
            'resolucao' => $resolucao,
            'resumo' => $resumo,
            'precisa_dialogo' => false,
            'precisa_confirmacao' => $precisaConfirmacao,
            'pergunta' => null,
            'campo_dialogo' => null,
            'intent_parcial' => $precisaConfirmacao ? $intent : null,
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
