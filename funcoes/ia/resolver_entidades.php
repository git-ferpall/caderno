<?php
declare(strict_types=1);

function iaNormalizarTexto(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return $s;
}

function iaSimilaridade(string $a, string $b): float
{
    $a = iaNormalizarTexto($a);
    $b = iaNormalizarTexto($b);
    if ($a === $b) {
        return 1.0;
    }
    if ($a === '' || $b === '') {
        return 0.0;
    }
    similar_text($a, $b, $pct);
    return $pct / 100.0;
}

function iaResolverEntidades(array $intent, array $contexto): array
{
    $areasCtx = $contexto['areas'] ?? [];
    $produtosCtx = $contexto['produtos'] ?? [];

    $areaIds = iaResolverIdsPorNomes($intent['area_nomes'] ?? [], $areasCtx);
    $produtoIds = iaResolverIdsPorNomes($intent['produto_nomes'] ?? [], $produtosCtx);

    $ambiguidades = [];
    $faltando = [];

    foreach ($intent['area_nomes'] ?? [] as $nome) {
        if ($nome === '') {
            continue;
        }
        $match = iaMelhorMatch($nome, $areasCtx);
        if (!$match['id']) {
            $faltando[] = "área \"{$nome}\"";
        } elseif ($match['score'] < 0.75) {
            $ambiguidades[] = "área \"{$nome}\" (melhor: {$match['label']})";
        }
    }

    foreach ($intent['produto_nomes'] ?? [] as $nome) {
        if ($nome === '') {
            continue;
        }
        $match = iaMelhorMatch($nome, $produtosCtx);
        if (!$match['id']) {
            $faltando[] = "produto \"{$nome}\"";
        } elseif ($match['score'] < 0.75) {
            $ambiguidades[] = "produto \"{$nome}\" (melhor: {$match['label']})";
        }
    }

    $confianca = (float) ($intent['confianca'] ?? 0.5);
    if ($faltando) {
        $confianca = min($confianca, 0.4);
    } elseif ($ambiguidades) {
        $confianca = min($confianca, 0.65);
    }

    return [
        'area_ids' => $areaIds,
        'produto_ids' => $produtoIds,
        'ambiguidades' => $ambiguidades,
        'faltando' => $faltando,
        'confianca' => $confianca,
    ];
}

function iaResolverIdsPorNomes(array $nomes, array $catalogo, float $minScore = 0.6): array
{
    $ids = [];
    foreach ($nomes as $nome) {
        $nome = trim((string) $nome);
        if ($nome === '') {
            continue;
        }
        $match = iaMelhorMatch($nome, $catalogo, $minScore);
        if ($match['id']) {
            $ids[] = $match['id'];
        }
    }
    return array_values(array_unique($ids));
}

function iaMelhorMatch(string $nome, array $catalogo, float $minScore = 0.0): array
{
    $bestId = null;
    $bestLabel = '';
    $bestScore = 0.0;

    foreach ($catalogo as $item) {
        $label = (string) ($item['nome'] ?? '');
        $score = iaSimilaridade($nome, $label);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = (int) ($item['id'] ?? 0);
            $bestLabel = $label;
        }
    }

    if ($bestScore < $minScore) {
        return ['id' => null, 'label' => $bestLabel, 'score' => $bestScore];
    }

    return ['id' => $bestId, 'label' => $bestLabel, 'score' => $bestScore];
}

function iaPrecisaConfirmacao(array $intent, array $resolucao): bool
{
    if (in_array($intent['acao'] ?? '', ['consultar', 'listar_pendentes'], true)) {
        return false;
    }
    $conf = (float) ($resolucao['confianca'] ?? $intent['confianca'] ?? 0);
    if ($intent['acao'] === 'desconhecido') {
        return true;
    }
    if ($resolucao['faltando'] ?? []) {
        return true;
    }
    if ($conf < 0.72) {
        return true;
    }
    if (($resolucao['ambiguidades'] ?? []) && $conf < 0.85) {
        return true;
    }
    return false;
}

function iaResumoIntent(array $intent, array $resolucao, array $contexto): string
{
    $acao = $intent['acao'] ?? 'desconhecido';
    $tipo = $intent['tipo'] ?? '';
    $data = $intent['data'] ?? date('Y-m-d');

    $areaNomes = [];
    foreach ($resolucao['area_ids'] ?? [] as $id) {
        foreach ($contexto['areas'] ?? [] as $a) {
            if ((int) $a['id'] === (int) $id) {
                $areaNomes[] = $a['nome'];
            }
        }
    }
    $prodNomes = [];
    foreach ($resolucao['produto_ids'] ?? [] as $id) {
        foreach ($contexto['produtos'] ?? [] as $p) {
            if ((int) $p['id'] === (int) $id) {
                $prodNomes[] = $p['nome'];
            }
        }
    }

    $areas = $areaNomes ? implode(', ', $areaNomes) : (implode(', ', $intent['area_nomes'] ?? []) ?: '—');
    $produtos = $prodNomes ? implode(', ', $prodNomes) : (implode(', ', $intent['produto_nomes'] ?? []) ?: '—');

    $qtd = $intent['quantidade'] ?? null;
    $un = $intent['unidade'] ?? '';
    $detalheQtd = ($qtd !== null && is_numeric($qtd))
        ? ' — ' . $qtd . ($un ? ' ' . $un : '')
        : '';

    $previsao = $intent['previsao_dias'] ?? null;
    $detalhePrev = ($previsao !== null && is_numeric($previsao) && (int) $previsao > 0)
        ? ', previsão ' . (int) $previsao . ' dias'
        : '';

    $obs = trim((string) ($intent['observacoes'] ?? ''));
    $detalheObs = $obs !== '' ? '. Obs: ' . mb_substr($obs, 0, 80) : '';

    return match ($acao) {
        'criar_apontamento' => sprintf(
            'Criar %s em %s (%s) — %s%s%s%s',
            $tipo ?: 'apontamento',
            $areas,
            $produtos,
            $data,
            $detalheQtd,
            $detalhePrev,
            $detalheObs
        ),
        'concluir_apontamento' => sprintf('Concluir %s em %s', $tipo ?: 'manejo', $areas),
        'listar_pendentes' => 'Listar manejos pendentes',
        'consultar' => 'Consulta: ' . ($intent['consulta'] ?? 'dados'),
        default => $intent['mensagem'] ?? 'Comando não reconhecido',
    };
}
