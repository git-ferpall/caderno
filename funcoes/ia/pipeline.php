<?php
declare(strict_types=1);

require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/dialogo.php';
require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/memoria.php';
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
        $transcricao = iaTranscreverAudio($filePath, $mime, $campoDialogo);
        return $this->processFromText($transcricao, $transcricao, $intentParcial, $campoDialogo);
    }

    public function processFromText(
        string $texto,
        ?string $transcricao = null,
        ?array $intentParcial = null,
        ?string $campoDialogo = null
    ): array {
        $contexto = iaContextoUsuario($this->mysqli, $this->user_id);
        $memoria = iaMemoriaCarregar($this->user_id);
        $texto = iaCorrigirTranscricaoPt(trim($texto), $campoDialogo);

        if ($intentParcial !== null && $campoDialogo !== null && $campoDialogo !== '') {
            $intent = iaMesclarRespostaDialogo($intentParcial, $campoDialogo, $texto, $contexto);
            $intent['_ultimo_campo'] = $campoDialogo;
            $intent['_ultimo_texto'] = $texto;
        } else {
            $intent = iaInterpretarComando($texto, $contexto, $memoria);
            $intent = iaRepararIntentCriarApontamento($intent, $texto);
            $intent = iaRepararIntentFollowUp($intent, $texto, $memoria);
            $intent = iaRepararIntentConsulta($intent, $texto);
            $intent = iaRepararIntentParaDialogo($intent, $texto);
            if (($intent['acao'] ?? '') === 'desconhecido') {
                $intent['mensagem'] = 'Hum, não peguei direito. Posso registrar manejos, consultar pendentes e colheitas, ou marcar como feito — o que você precisa?';
            }
        }

        $resultado = $this->finalizarIntent($intent, $transcricao ?? $texto, $contexto);
        $this->registrarMemoria($texto, $resultado);

        return $resultado;
    }

    /** Ações rápidas da UI (botões nos cards) — sem passar pelo GPT. */
    public function processFromIntent(array $intent, string $textoUsuario = 'Ação rápida'): array
    {
        $contexto = iaContextoUsuario($this->mysqli, $this->user_id);
        $intent = iaNormalizarIntent($intent);
        $resultado = $this->finalizarIntent($intent, $textoUsuario, $contexto);
        $this->registrarMemoria($textoUsuario, $resultado);

        return $resultado;
    }

    private function registrarMemoria(string $textoUsuario, array $resultado): void
    {
        $msgAssistente = (string) ($resultado['fala'] ?? $resultado['msg'] ?? $resultado['pergunta'] ?? '');
        $extra = iaMemoriaExtrairExtrasResultado($resultado);
        if (!empty($resultado['apontamento_id'])) {
            $extra['apontamento_id'] = (int) $resultado['apontamento_id'];
        }
        iaMemoriaRegistrarTurno($this->user_id, $textoUsuario, $msgAssistente, $extra);
    }

    private function finalizarIntent(array $intent, string $transcricao, array $contexto): array
    {
        $intent = iaMarcarFlagsDialogoPreenchidos($intent);
        if (($intent['acao'] ?? '') === 'concluir_apontamento') {
            $intent = iaPrepararIntentConcluir($intent, $contexto);
        }
        $resolucao = iaResolverEntidades($intent, $contexto);
        $resumo = iaResumoIntent($intent, $resolucao, $contexto);
        $perguntaDialogo = iaProximaPergunta($intent, $resolucao, $contexto);

        if ($perguntaDialogo !== null && iaDeveDialogar($intent, $resolucao, $contexto)) {
            $campo = (string) $perguntaDialogo['campo'];
            $pergunta = (string) $perguntaDialogo['pergunta'];
            $progresso = iaProgressoDialogo($intent, $campo);
            $fala = iaMontarFalaAssistente($intent, $pergunta, $campo, $contexto);
            $intentCliente = iaLimparIntentCliente($intent);
            $dialogoOpcoes = iaDialogoOpcoes($campo, $intent, $contexto);

            return [
                'ok' => true,
                'transcricao' => $transcricao,
                'intent' => $intentCliente,
                'resolucao' => $this->resolucaoCliente($resolucao),
                'resumo' => $resumo,
                'precisa_dialogo' => true,
                'precisa_confirmacao' => false,
                'pergunta' => $pergunta,
                'fala' => $fala,
                'campo_dialogo' => $campo,
                'dialogo_passo' => $progresso['passo'],
                'dialogo_total' => $progresso['total'],
                'dialogo_opcoes' => $dialogoOpcoes ?: null,
                'intent_parcial' => $intentCliente,
                'executado' => false,
                'msg' => $pergunta,
            ];
        }

        $acao = $intent['acao'] ?? 'desconhecido';
        $precisaConfirmacao = iaPrecisaConfirmacao($intent, $resolucao);
        $resultado = null;

        if (!$precisaConfirmacao && $acao !== 'desconhecido') {
            $resultado = $this->executeIntent($intent, $resolucao);
        }

        $executado = (bool) ($resultado['executado'] ?? false);
        $msgFinal = $resultado['msg'] ?? ($intent['mensagem'] ?? $resumo);

        if ($resultado !== null && !($resultado['ok'] ?? false) && !$executado) {
            return [
                'ok' => true,
                'transcricao' => $transcricao,
                'intent' => iaLimparIntentCliente($intent),
                'resolucao' => $this->resolucaoCliente($resolucao),
                'resumo' => $resumo,
                'precisa_dialogo' => false,
                'precisa_confirmacao' => false,
                'pergunta' => null,
                'fala' => $msgFinal,
                'campo_dialogo' => null,
                'intent_parcial' => null,
                'executado' => false,
                'consulta' => $resultado['consulta'] ?? null,
                'consulta_dados' => $resultado['dados'] ?? null,
                'msg' => $msgFinal,
            ];
        }

        if ($precisaConfirmacao && $acao !== 'desconhecido' && !$executado) {
            return [
                'ok' => true,
                'transcricao' => $transcricao,
                'intent' => iaLimparIntentCliente($intent),
                'resolucao' => $this->resolucaoCliente($resolucao),
                'resumo' => $resumo,
                'precisa_dialogo' => false,
                'precisa_confirmacao' => true,
                'pergunta' => null,
                'fala' => 'Pronto, anotei tudo. ' . $resumo . ' Confirmo e salvo?',
                'campo_dialogo' => null,
                'intent_parcial' => iaLimparIntentCliente($intent),
                'executado' => false,
                'msg' => $resumo,
            ];
        }

        return [
            'ok' => true,
            'transcricao' => $transcricao,
            'intent' => iaLimparIntentCliente($intent),
            'resolucao' => $this->resolucaoCliente($resolucao),
            'resumo' => $resumo,
            'precisa_dialogo' => false,
            'precisa_confirmacao' => false,
            'pergunta' => null,
            'fala' => $executado ? $msgFinal : null,
            'campo_dialogo' => null,
            'intent_parcial' => null,
            'executado' => $executado,
            'consulta' => $resultado['consulta'] ?? null,
            'consulta_dados' => $resultado['dados'] ?? null,
            'apontamento_id' => $resultado['apontamento_id'] ?? null,
            'msg' => $msgFinal,
        ];
    }

    private function resolucaoCliente(array $resolucao): array
    {
        return [
            'area_ids' => $resolucao['area_ids'] ?? [],
            'produto_ids' => $resolucao['produto_ids'] ?? [],
            'confianca' => $resolucao['confianca'] ?? 0,
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
