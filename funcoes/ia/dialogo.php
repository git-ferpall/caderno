<?php
declare(strict_types=1);

/** Tipos de manejo que o assistente consegue criar via diálogo. */
function iaTiposComDialogo(): array
{
    return ['irrigacao', 'colheita', 'semeadura', 'plantio', 'personalizado'];
}

function iaFormatarListaNomes(array $items, string $campo = 'nome', int $max = 12): string
{
    if (!$items) {
        return '';
    }
    $nomes = array_filter(array_map(
        static fn ($item) => trim((string) ($item[$campo] ?? '')),
        array_slice($items, 0, $max)
    ));
    if (!$nomes) {
        return '';
    }
    $lista = implode(', ', $nomes);
    if (count($items) > $max) {
        $lista .= ' e outras';
    }
    return $lista;
}

function iaUsuarioPulouCampo(string $texto): bool
{
    $t = iaNormalizarTexto($texto);
    return (bool) preg_match(
        '/\b(?:pular|pul[ae]r|n[aã]o|sem|nenhum|nao|dispens|ignor|pr[oó]ximo|deixa|skip)\b/u',
        $t
    );
}

function iaNormalizarDataResposta(string $texto): ?string
{
    $t = iaNormalizarTexto($texto);

    if (preg_match('/\bhoje\b/u', $t)) {
        return date('Y-m-d');
    }
    if (preg_match('/\bontem\b/u', $t)) {
        return date('Y-m-d', strtotime('-1 day'));
    }
    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/u', $t, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }
    if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/u', $t, $m)) {
        $dia = (int) $m[1];
        $mes = (int) $m[2];
        $ano = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');
        if ($ano < 100) {
            $ano += 2000;
        }
        if (checkdate($mes, $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        }
    }

    $meses = [
        'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'março' => 3, 'abril' => 4,
        'maio' => 5, 'junho' => 6, 'julho' => 7, 'agosto' => 8,
        'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];
    if (preg_match('/\b(\d{1,2})\s+de\s+(\w+)(?:\s+de\s+(\d{4}))?\b/u', $t, $m)) {
        $dia = (int) $m[1];
        $mesNome = iaNormalizarTexto($m[2]);
        $ano = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');
        if (isset($meses[$mesNome]) && checkdate($meses[$mesNome], $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $meses[$mesNome], $dia);
        }
    }

    return null;
}

/**
 * Converte "informações insuficientes" / desconhecido em criar_apontamento para o diálogo.
 */
function iaRepararIntentParaDialogo(array $intent, string $texto): array
{
    $intent = iaNormalizarIntent($intent);
    $t = iaNormalizarTexto($texto);

    $querCriar = (bool) preg_match(
        '/\b(?:adicionar|registrar|lan[cç]ar|criar|novo|incluir|fazer|apontamento|manejo)\b/u',
        $t
    );

    if (empty($intent['tipo'])) {
        $tipoDetectado = iaNormalizarTipoManejo($texto);
        if ($tipoDetectado) {
            $intent['tipo'] = $tipoDetectado;
        }
    }

    if (($intent['acao'] ?? '') === 'desconhecido') {
        if ($querCriar || !empty($intent['tipo'])) {
            $intent['acao'] = 'criar_apontamento';
            $intent['confianca'] = max((float) ($intent['confianca'] ?? 0.3), 0.75);
            if (str_contains(mb_strtolower((string) ($intent['mensagem'] ?? '')), 'insuficient')) {
                $intent['mensagem'] = 'Vamos completar o apontamento passo a passo.';
            }
        }
    }

    return $intent;
}

/**
 * Próxima pergunta para completar o apontamento (null = pronto para confirmar).
 *
 * @return array{campo: string, pergunta: string}|null
 */
function iaProximaPergunta(array $intent, array $resolucao, array $contexto): ?array
{
    if (($intent['acao'] ?? '') !== 'criar_apontamento') {
        return null;
    }

    $tipo = (string) ($intent['tipo'] ?? '');

    if ($tipo === '') {
        return [
            'campo' => 'tipo',
            'pergunta' => 'Qual tipo de manejo? Plantio, semeadura, colheita ou irrigação?',
        ];
    }

    if (!in_array($tipo, iaTiposComDialogo(), true)) {
        return null;
    }

    if (empty($intent['_data_respondida'])) {
        return [
            'campo' => 'data',
            'pergunta' => 'Me diga a data do manejo. Pode falar hoje, ontem ou o dia — por exemplo, 15 de maio.',
        ];
    }

    if (empty($resolucao['area_ids'])) {
        $lista = iaFormatarListaNomes($contexto['areas'] ?? []);
        if (empty($intent['area_nomes'])) {
            $hint = $lista ? ' Tenho estas áreas cadastradas: ' . $lista . '.' : '';
            return [
                'campo' => 'area',
                'pergunta' => 'Em qual área, talhão ou bancada?' . $hint,
            ];
        }

        return [
            'campo' => 'area',
            'pergunta' => 'Hmm, não achei essa área.' . ($lista ? ' As cadastradas são: ' . $lista . '.' : '')
                . ' Qual delas você quis dizer?',
        ];
    }

    if ($tipo !== 'personalizado' && empty($resolucao['produto_ids'])) {
        $lista = iaFormatarListaNomes($contexto['produtos'] ?? []);
        if (empty($intent['produto_nomes'])) {
            $hint = $lista ? ' Produtos disponíveis: ' . $lista . '.' : '';
            return [
                'campo' => 'produto',
                'pergunta' => 'Qual cultura ou produto?' . $hint,
            ];
        }

        return [
            'campo' => 'produto',
            'pergunta' => 'Não encontrei esse produto.' . ($lista ? ' Cadastrados: ' . $lista . '.' : '')
                . ' Qual é o correto?',
        ];
    }

    if ($tipo === 'personalizado' && trim((string) ($intent['titulo'] ?? '')) === '') {
        return [
            'campo' => 'titulo',
            'pergunta' => 'Qual o título deste manejo personalizado?',
        ];
    }

    $perguntaQtd = iaPerguntaQuantidade($tipo);
    if ($perguntaQtd !== null) {
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd) || (float) $qtd <= 0) {
            return ['campo' => 'quantidade', 'pergunta' => $perguntaQtd];
        }
    }

    if ($tipo === 'plantio' && empty($intent['_previsao_respondida'])) {
        return [
            'campo' => 'previsao',
            'pergunta' => 'Quantos dias para a previsão de colheita? Se não quiser marcar, diga pular.',
        ];
    }

    if (empty($intent['_obs_respondida'])) {
        return [
            'campo' => 'observacoes',
            'pergunta' => 'Quer acrescentar alguma observação? Se não, diga pular.',
        ];
    }

    return null;
}

function iaPerguntaQuantidade(string $tipo): ?string
{
    return match ($tipo) {
        'irrigacao' => 'Qual volume foi irrigado? Litros ou metros cúbicos.',
        'colheita' => 'Quanto colheu? Pode falar em quilos, caixas ou sacas.',
        'semeadura' => 'Qual a quantidade semeada? Sementes, bandejas, mudas ou quilos.',
        'plantio' => 'Qual a quantidade plantada? Mudas, sacas, bandejas, caixas ou quilos.',
        default => null,
    };
}

/** Ordem dos passos do diálogo (para barra de progresso). */
function iaCamposDialogoOrdem(array $intent): array
{
    $tipo = (string) ($intent['tipo'] ?? '');
    $campos = [];
    if ($tipo === '') {
        $campos[] = 'tipo';
    }
    $campos[] = 'data';
    $campos[] = 'area';
    if ($tipo === 'personalizado') {
        $campos[] = 'titulo';
    } elseif ($tipo !== '') {
        $campos[] = 'produto';
    }
    if (in_array($tipo, ['irrigacao', 'colheita', 'semeadura', 'plantio'], true)) {
        $campos[] = 'quantidade';
    }
    if ($tipo === 'plantio') {
        $campos[] = 'previsao';
    }
    $campos[] = 'observacoes';
    return $campos;
}

function iaProgressoDialogo(array $intent, string $campoAtual): array
{
    $ordem = iaCamposDialogoOrdem($intent);
    $idx = array_search($campoAtual, $ordem, true);
    $passo = $idx === false ? 1 : $idx + 1;
    return ['passo' => $passo, 'total' => max(1, count($ordem))];
}

function iaNomeTipoManejo(?string $tipo): string
{
    return match ($tipo) {
        'plantio' => 'plantio',
        'semeadura' => 'semeadura',
        'colheita' => 'colheita',
        'irrigacao' => 'irrigação',
        'personalizado' => 'manejo personalizado',
        default => 'apontamento',
    };
}

function iaFormatarDataFala(string $data): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return $data;
    }
    if ($data === date('Y-m-d')) {
        return 'hoje';
    }
    if ($data === date('Y-m-d', strtotime('-1 day'))) {
        return 'ontem';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $data;
}

function iaConfirmarResposta(string $campo, string $texto, array $intent): string
{
    return match ($campo) {
        'tipo' => 'Certo, ' . iaNomeTipoManejo((string) ($intent['tipo'] ?? '')) . '.',
        'data' => 'Ok, data ' . iaFormatarDataFala((string) ($intent['data'] ?? '')) . '.',
        'area' => 'Beleza, área ' . ($intent['area_nomes'][0] ?? $texto) . '.',
        'produto' => 'Entendi, ' . ($intent['produto_nomes'][0] ?? $texto) . '.',
        'quantidade' => 'Anotado, '
            . ($intent['quantidade'] ?? '')
            . (($intent['unidade'] ?? '') ? ' ' . $intent['unidade'] : '') . '.',
        'previsao' => empty($intent['previsao_dias'])
            ? 'Sem previsão de colheita.'
            : 'Previsão de ' . (int) $intent['previsao_dias'] . ' dias.',
        'observacoes' => trim((string) ($intent['observacoes'] ?? '')) !== ''
            ? 'Observação registrada.'
            : 'Sem observações.',
        'titulo' => 'Título anotado.',
        default => 'Ok.',
    };
}

function iaMontarFalaAssistente(array $intent, string $pergunta, string $campoAtual): string
{
    $partes = [];

    if (!empty($intent['_ultimo_campo']) && !empty($intent['_ultimo_texto'])) {
        $partes[] = iaConfirmarResposta(
            (string) $intent['_ultimo_campo'],
            (string) $intent['_ultimo_texto'],
            $intent
        );
    } elseif ($campoAtual === 'data' && !empty($intent['tipo']) && empty($intent['_data_respondida'])) {
        $partes[] = 'Entendi, vamos registrar um ' . iaNomeTipoManejo((string) $intent['tipo']) . '.';
    } elseif ($campoAtual === 'tipo') {
        $partes[] = 'Vou te ajudar a registrar o manejo.';
    }

    $partes[] = $pergunta;

    return trim(implode(' ', array_filter($partes)));
}

function iaLimparIntentCliente(array $intent): array
{
    unset(
        $intent['_ultimo_campo'],
        $intent['_ultimo_texto'],
        $intent['_dialogo_ack']
    );
    return $intent;
}

function iaDeveDialogar(array $intent, array $resolucao, array $contexto): bool
{
    return iaProximaPergunta($intent, $resolucao, $contexto) !== null;
}

function iaInferirUnidade(string $texto): ?string
{
    $t = mb_strtolower($texto);
    if (preg_match('/\bm[³3]|metro c/u', $t)) {
        return 'm3';
    }
    if (preg_match('/litro/u', $t)) {
        return 'litros';
    }
    if (preg_match('/\bkg|quilo/u', $t)) {
        return 'kg';
    }
    if (preg_match('/caixa/u', $t)) {
        return 'caixas';
    }
    if (preg_match('/saca/u', $t)) {
        return 'sacas';
    }
    if (preg_match('/semente/u', $t)) {
        return 'sementes';
    }
    if (preg_match('/bandeja/u', $t)) {
        return 'bandejas';
    }
    if (preg_match('/muda/u', $t)) {
        return 'mudas';
    }
    return null;
}

function iaExtrairNumero(string $texto): ?float
{
    if (preg_match('/(\d+(?:[.,]\d+)?)/u', $texto, $m)) {
        return (float) str_replace(',', '.', $m[1]);
    }
    return null;
}

function iaNormalizarTipoManejo(string $texto): ?string
{
    $t = iaNormalizarTexto(iaCorrigirTranscricaoPt($texto, 'tipo'));
    if (str_contains($t, 'irrig')) {
        return 'irrigacao';
    }
    if (str_contains($t, 'colh')) {
        return 'colheita';
    }
    if (preg_match('/\b(?:semead|sem(?:ei|ead)?)\b/u', $t)) {
        return 'semeadura';
    }
    if (preg_match('/\b(?:plant(?:io|ei|ar|o)?|plan(?:to|tei)?)\b/u', $t) || preg_match('/\bplan\s*[12]\b/u', $t)) {
        return 'plantio';
    }
    if (str_contains($t, 'personal')) {
        return 'personalizado';
    }
    return null;
}

function iaNormalizarTipoSemeadura(string $texto): ?string
{
    $t = iaNormalizarTexto($texto);
    foreach (['Direta', 'Bandeja', 'Canteiro', 'Replantio'] as $tipo) {
        if (str_contains($t, iaNormalizarTexto($tipo))) {
            return $tipo;
        }
    }
    if (str_contains($t, 'bandeja')) {
        return 'Bandeja';
    }
    if (str_contains($t, 'canteiro')) {
        return 'Canteiro';
    }
    if (str_contains($t, 'replanti')) {
        return 'Replantio';
    }
    return 'Direta';
}

function iaUnidadePadraoPorTipo(string $tipo): ?string
{
    return match ($tipo) {
        'colheita' => 'kg',
        'irrigacao' => 'litros',
        'semeadura' => 'sementes',
        'plantio' => 'mudas',
        default => null,
    };
}

/**
 * Incorpora a resposta falada/digitada no intent parcial.
 */
function iaMesclarRespostaDialogo(array $intent, string $campo, string $texto, array $contexto): array
{
    $texto = trim($texto);
    $intent = iaNormalizarIntent($intent);

    switch ($campo) {
        case 'tipo':
            $tipo = iaNormalizarTipoManejo($texto);
            if ($tipo) {
                $intent['tipo'] = $tipo;
            }
            break;

        case 'data':
            $intent['_data_respondida'] = true;
            $data = iaNormalizarDataResposta($texto);
            if ($data) {
                $intent['data'] = $data;
            }
            break;

        case 'area':
            $match = iaMelhorMatch($texto, $contexto['areas'] ?? [], 0.45);
            $intent['area_nomes'] = [$match['label'] ?: $texto];
            break;

        case 'produto':
            $match = iaMelhorMatch($texto, $contexto['produtos'] ?? [], 0.45);
            $intent['produto_nomes'] = [$match['label'] ?: $texto];
            break;

        case 'quantidade':
            $num = iaExtrairNumero($texto);
            if ($num !== null) {
                $intent['quantidade'] = $num;
            }
            $un = iaInferirUnidade($texto);
            if ($un) {
                $intent['unidade'] = $un;
            } elseif (empty($intent['unidade'])) {
                $padrao = iaUnidadePadraoPorTipo((string) ($intent['tipo'] ?? ''));
                if ($padrao) {
                    $intent['unidade'] = $padrao;
                }
            }
            break;

        case 'tipo_semeadura':
            $intent['tipo_semeadura'] = iaNormalizarTipoSemeadura($texto);
            break;

        case 'previsao':
            $intent['_previsao_respondida'] = true;
            if (iaUsuarioPulouCampo($texto)) {
                $intent['previsao_dias'] = null;
            } else {
                $num = iaExtrairNumero($texto);
                if ($num !== null && $num > 0) {
                    $intent['previsao_dias'] = (int) round($num);
                }
            }
            break;

        case 'observacoes':
            $intent['_obs_respondida'] = true;
            if (!iaUsuarioPulouCampo($texto)) {
                $intent['observacoes'] = $texto;
            }
            break;

        case 'titulo':
            $intent['titulo'] = $texto;
            break;
    }

    $intent['confianca'] = min(1.0, ((float) ($intent['confianca'] ?? 0.5)) + 0.15);

    return iaNormalizarIntent($intent);
}
