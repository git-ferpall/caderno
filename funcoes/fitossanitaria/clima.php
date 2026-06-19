<?php
declare(strict_types=1);

/** @return array<int, array<string, mixed>> */
function fsBuscarRegistrosClimaticos(mysqli $mysqli, int $propriedadeId, int $dias = 14): array
{
    $desde = date('Y-m-d', strtotime("-{$dias} days"));

    $sql = "
        SELECT
            a.id,
            a.data,
            a.observacoes,
            MAX(CASE WHEN ad.campo = 'tipo_registro' THEN ad.valor END) AS tipo,
            MAX(CASE WHEN ad.campo = 'valor' THEN ad.valor END) AS valor
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id
        WHERE a.propriedade_id = ?
          AND a.tipo = 'clima'
          AND a.data >= ?
        GROUP BY a.id, a.data, a.observacoes
        ORDER BY a.data DESC, a.id DESC
        LIMIT 30
    ";

    try {
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $propriedadeId, $desde);
        $stmt->execute();
        $res = $stmt->get_result();

        $lista = [];
        while ($row = $res->fetch_assoc()) {
            $lista[] = [
                'id' => (int) $row['id'],
                'data' => (string) $row['data'],
                'tipo' => (string) ($row['tipo'] ?? ''),
                'valor' => (string) ($row['valor'] ?? ''),
                'observacoes' => (string) ($row['observacoes'] ?? ''),
            ];
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('fitossanitaria clima: ' . $e->getMessage());
        return [];
    }

    return $lista;
}

function fsUltimoRegistroClimaPorTipo(array $registros, string $tipo): ?array
{
    foreach ($registros as $r) {
        if (($r['tipo'] ?? '') === $tipo) {
            return $r;
        }
    }
    return null;
}

function fsExtrairNumero(string $valor): ?float
{
    if (preg_match('/-?\d+[.,]?\d*/', $valor, $m)) {
        return (float) str_replace(',', '.', $m[0]);
    }
    return null;
}

function fsAvaliarClimaAplicacao(array $registros): array
{
    $alertas = [];
    $recomendacao = 'Condições climáticas não registradas recentemente — registre no caderno antes de aplicar.';

    if (!$registros) {
        return [
            'nivel' => 'indeterminado',
            'aplicacao_recomendada' => null,
            'alertas' => ['Sem registros climáticos nos últimos 14 dias.'],
            'resumo' => 'Sem dados climáticos recentes na propriedade.',
            'registros_recentes' => [],
        ];
    }

    $vento = fsUltimoRegistroClimaPorTipo($registros, 'vento');
    $chuva = fsUltimoRegistroClimaPorTipo($registros, 'chuva');
    $temp = fsUltimoRegistroClimaPorTipo($registros, 'temperatura');
    $umidade = fsUltimoRegistroClimaPorTipo($registros, 'umidade');

    $aplicacaoOk = true;

    if ($vento) {
        $v = fsExtrairNumero((string) $vento['valor']);
        if ($v !== null && $v > 15) {
            $alertas[] = sprintf('Vento registrado em %s: %.1f — risco de deriva.', $vento['data'], $v);
            $aplicacaoOk = false;
        }
    }

    if ($chuva) {
        $c = fsExtrairNumero((string) $chuva['valor']);
        if ($c !== null && $c > 0) {
            $alertas[] = sprintf('Chuva registrada em %s (%.1f mm) — aguardar condições secas.', $chuva['data'], $c);
            $aplicacaoOk = false;
        }
    }

    if ($temp) {
        $t = fsExtrairNumero((string) $temp['valor']);
        if ($t !== null && ($t < 10 || $t > 35)) {
            $alertas[] = sprintf('Temperatura em %s: %.1f °C — fora da faixa ideal para aplicação.', $temp['data'], $t);
            $aplicacaoOk = false;
        }
    }

    if ($umidade && fsExtrairNumero((string) $umidade['valor']) !== null) {
        $u = fsExtrairNumero((string) $umidade['valor']);
        if ($u !== null && $u < 40) {
            $alertas[] = sprintf('Umidade baixa (%s: %.0f%%) — evaporação rápida.', $umidade['data'], $u);
        }
    }

    if ($aplicacaoOk && !$alertas) {
        $recomendacao = 'Últimos registros climáticos sem alertas críticos. Confirme condições no momento da aplicação.';
    } elseif (!$aplicacaoOk) {
        $recomendacao = 'Aplicação não recomendada agora com base nos registros climáticos recentes.';
    } else {
        $recomendacao = 'Há observações climáticas — revise antes de aplicar defensivo.';
    }

    return [
        'nivel' => $aplicacaoOk ? ($alertas ? 'moderado' : 'baixo') : 'alto',
        'aplicacao_recomendada' => $aplicacaoOk,
        'alertas' => $alertas,
        'resumo' => $alertas ? implode(' ', array_slice($alertas, 0, 2)) : 'Clima recente sem bloqueios para aplicação.',
        'registros_recentes' => array_slice($registros, 0, 6),
        'ultimo_vento' => $vento,
        'ultima_chuva' => $chuva,
        'ultima_temperatura' => $temp,
        'recomendacao' => $recomendacao,
    ];
}
