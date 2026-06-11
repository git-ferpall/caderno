<?php
declare(strict_types=1);

/** Tipos de manejo que o assistente consegue criar via diálogo. */
function iaTiposComDialogo(): array
{
    return ['irrigacao', 'colheita', 'semeadura', 'personalizado'];
}

/**
 * Próxima pergunta para completar o apontamento (null = nada crítico faltando).
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

    if (empty($resolucao['area_ids'])) {
        if (empty($intent['area_nomes'])) {
            $nomes = array_slice(array_column($contexto['areas'] ?? [], 'nome'), 0, 4);
            $hint = $nomes ? ' Suas áreas: ' . implode(', ', $nomes) . '.' : '';
            return [
                'campo' => 'area',
                'pergunta' => 'Qual área, talhão ou bancada?' . $hint,
            ];
        }

        return [
            'campo' => 'area',
            'pergunta' => 'Não encontrei essa área no cadastro. Pode repetir o nome da área?',
        ];
    }

    if ($tipo !== 'personalizado' && empty($resolucao['produto_ids'])) {
        if (empty($intent['produto_nomes'])) {
            $nomes = array_slice(array_column($contexto['produtos'] ?? [], 'nome'), 0, 4);
            $hint = $nomes ? ' Exemplos: ' . implode(', ', $nomes) . '.' : '';
            return [
                'campo' => 'produto',
                'pergunta' => 'Qual cultura ou produto?' . $hint,
            ];
        }

        return [
            'campo' => 'produto',
            'pergunta' => 'Não encontrei esse produto. Qual o nome da cultura?',
        ];
    }

    if ($tipo === 'irrigacao') {
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd) || (float) $qtd <= 0) {
            return [
                'campo' => 'quantidade',
                'pergunta' => 'Qual o volume irrigado? Diga em litros ou metros cúbicos.',
            ];
        }
    }

    if ($tipo === 'colheita') {
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd)) {
            return [
                'campo' => 'quantidade',
                'pergunta' => 'Qual a quantidade colhida? Por exemplo, 10 kg ou 5 caixas.',
            ];
        }
    }

    if ($tipo === 'semeadura') {
        if (empty($intent['tipo_semeadura'])) {
            return [
                'campo' => 'tipo_semeadura',
                'pergunta' => 'Qual o tipo de semeadura? Direta, bandeja, canteiro ou replantio?',
            ];
        }
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd) || (float) $qtd <= 0) {
            return [
                'campo' => 'quantidade',
                'pergunta' => 'Qual a quantidade semeada? Por exemplo, 300 sementes ou 2 bandejas.',
            ];
        }
    }

    if ($tipo === 'personalizado' && trim((string) ($intent['titulo'] ?? '')) === '') {
        return [
            'campo' => 'titulo',
            'pergunta' => 'Qual o título deste manejo personalizado?',
        ];
    }

    return null;
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
    if (preg_match('/\b(?:plant(?:io|ei|ar|o)?|plan(?:to|tei)?|semead|sem(?:ei|ead)?)\b/u', $t)) {
        return 'semeadura';
    }
    if (preg_match('/\bplan\s*[12]\b/u', $t)) {
        return 'semeadura';
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
            } elseif (($intent['tipo'] ?? '') === 'colheita' && empty($intent['unidade'])) {
                $intent['unidade'] = 'kg';
            } elseif (($intent['tipo'] ?? '') === 'irrigacao' && empty($intent['unidade'])) {
                $intent['unidade'] = 'litros';
            } elseif (($intent['tipo'] ?? '') === 'semeadura' && empty($intent['unidade'])) {
                $intent['unidade'] = 'sementes';
            }
            break;

        case 'tipo_semeadura':
            $intent['tipo_semeadura'] = iaNormalizarTipoSemeadura($texto);
            break;

        case 'titulo':
            $intent['titulo'] = $texto;
            break;
    }

    $intent['confianca'] = min(1.0, ((float) ($intent['confianca'] ?? 0.5)) + 0.15);

    return iaNormalizarIntent($intent);
}
