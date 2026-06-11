<?php
declare(strict_types=1);

require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/dialogo.php';

/** Tipos de consulta que o agente executa no banco. */
function iaConsultasPermitidas(): array
{
    return [
        'contar_pendentes',
        'listar_pendentes',
        'ultima_colheita',
        'ultimo_manejo',
        'resumo_manejos',
        'total_colheita',
    ];
}

/**
 * Detecta perguntas sobre dados e converte em acao=consultar (complementa o GPT).
 */
function iaRepararIntentConsulta(array $intent, string $texto): array
{
    if (($intent['acao'] ?? '') === 'consultar' && !empty($intent['consulta'])) {
        return iaNormalizarIntentConsulta($intent);
    }

    $t = iaNormalizarTexto($texto);

    if (preg_match('/\b(?:adicionar|registrar|lan[cç]ar|criar|novo|incluir|aplicar)\b/u', $t)
        && !preg_match('/\?|^(?:quantos|quanto|qual|quais|me (?:fala|diz|conta))/u', $t)) {
        return $intent;
    }

    if (preg_match('/pendente|falt(a|am|ando)|nao fiz|não fiz|falta fazer|o que (?:tenho|falta)/u', $t)) {
        if (preg_match('/quantos|quantas|numero|número|total|conta/u', $t)) {
            return iaIntentConsulta('contar_pendentes', $intent);
        }
        if (preg_match('/listar|quais|mostr|me fala|me diz|detalh/u', $t) || ($intent['acao'] ?? '') === 'listar_pendentes') {
            return iaIntentConsulta('listar_pendentes', $intent);
        }
        return iaIntentConsulta('contar_pendentes', $intent);
    }

    if (preg_match('/ultim[ao].*colh|colh.*ultim|quanto (?:eu )?colh|quanto colhi|ultima colheita/u', $t)) {
        return iaIntentConsulta('ultima_colheita', $intent);
    }

    if (preg_match('/total.*colh|colh.*(?:mes|mês|semana|periodo|período)/u', $t)) {
        $intent = iaIntentConsulta('total_colheita', $intent);
        $intent['periodo'] = iaDetectarPeriodoTexto($t);
        return $intent;
    }

    if (preg_match('/ultim[ao].*(?:manejo|irrig|plant|herbi|fungi|inset|ferti|apont)/u', $t)) {
        $intent = iaIntentConsulta('ultimo_manejo', $intent);
        $tipo = iaNormalizarTipoManejo($texto);
        if ($tipo) {
            $intent['tipo'] = $tipo;
        }
        return $intent;
    }

    if (preg_match('/resumo|quantos manej|quantos apont|hist[oó]rico|esse m[eê]s|esta semana|na semana|no m[eê]s/u', $t)) {
        $intent = iaIntentConsulta('resumo_manejos', $intent);
        $intent['periodo'] = iaDetectarPeriodoTexto($t);
        return $intent;
    }

    if (($intent['acao'] ?? '') === 'listar_pendentes') {
        return iaIntentConsulta('listar_pendentes', $intent);
    }

    return $intent;
}

function iaIntentConsulta(string $consulta, array $intent, array $extra = []): array
{
    $intent['acao'] = 'consultar';
    $intent['consulta'] = $consulta;
    $intent['confianca'] = max((float) ($intent['confianca'] ?? 0), 0.9);
    return iaNormalizarIntentConsulta(array_merge($intent, $extra));
}

function iaNormalizarIntentConsulta(array $intent): array
{
    $consulta = (string) ($intent['consulta'] ?? '');
    if (!in_array($consulta, iaConsultasPermitidas(), true)) {
        $intent['consulta'] = 'contar_pendentes';
    }

    $periodos = ['semana', 'mes', '30_dias', '7_dias', 'ano'];
    $p = (string) ($intent['periodo'] ?? '30_dias');
    $intent['periodo'] = in_array($p, $periodos, true) ? $p : '30_dias';

    return $intent;
}

function iaDetectarPeriodoTexto(string $t): string
{
    $t = iaNormalizarTexto($t);
    if (preg_match('/semana/u', $t)) {
        return 'semana';
    }
    if (preg_match('/\b(?:mes|mês)\b/u', $t)) {
        return 'mes';
    }
    if (preg_match('/\b(?:ano|anual)\b/u', $t)) {
        return 'ano';
    }
    if (preg_match('/\b7 dias\b/u', $t)) {
        return '7_dias';
    }
    return '30_dias';
}

/** Intervalo [inicio, fim] YYYY-MM-DD para consultas por período. */
function iaIntervaloPeriodo(string $periodo): array
{
    $fim = date('Y-m-d');
    $inicio = match ($periodo) {
        'semana' => date('Y-m-d', strtotime('monday this week')),
        'mes' => date('Y-m-01'),
        '7_dias' => date('Y-m-d', strtotime('-7 days')),
        'ano' => date('Y-01-01'),
        default => date('Y-m-d', strtotime('-30 days')),
    };
    return [$inicio, $fim];
}

function iaExecutarConsulta(mysqli $mysqli, int $user_id, array $intent, array $contexto): array
{
    $prop = obterPropriedadeAtiva($mysqli, $user_id);
    if (!$prop) {
        return ['ok' => false, 'executado' => false, 'msg' => 'Não encontrei propriedade ativa no seu cadastro.'];
    }

    $propriedade_id = (int) $prop['id'];
    $consulta = (string) ($intent['consulta'] ?? 'contar_pendentes');

    return match ($consulta) {
        'contar_pendentes' => iaConsultaContarPendentes($mysqli, $propriedade_id),
        'listar_pendentes' => iaConsultaListarPendentes($mysqli, $propriedade_id),
        'ultima_colheita' => iaConsultaUltimaColheita($mysqli, $propriedade_id, $intent, $contexto),
        'ultimo_manejo' => iaConsultaUltimoManejo($mysqli, $propriedade_id, $intent, $contexto),
        'resumo_manejos' => iaConsultaResumoManejos($mysqli, $propriedade_id, (string) ($intent['periodo'] ?? '30_dias')),
        'total_colheita' => iaConsultaTotalColheita($mysqli, $propriedade_id, $intent, $contexto),
        default => ['ok' => false, 'executado' => false, 'msg' => 'Consulta não reconhecida.'],
    };
}

function iaConsultaContarPendentes(mysqli $mysqli, int $propriedade_id): array
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM apontamentos WHERE propriedade_id = ? AND status = 'pendente'");
    $stmt->bind_param('i', $propriedade_id);
    $stmt->execute();
    $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    if ($total === 0) {
        return [
            'ok' => true,
            'executado' => true,
            'msg' => 'Boa notícia: você não tem nenhum apontamento pendente no momento.',
            'consulta' => 'contar_pendentes',
            'dados' => ['total' => 0],
        ];
    }

    $itens = iaListarPendentesResumo($mysqli, $propriedade_id, 3);
    $extras = array_map(static fn ($i) => iaFormatarLinhaManejo($i), $itens);
    $lista = implode('; ', $extras);
    $resto = $total > count($itens) ? '… e mais ' . ($total - count($itens)) . '.' : '.';

    return [
        'ok' => true,
        'executado' => true,
        'msg' => "Você tem {$total} apontamento" . ($total > 1 ? 's' : '') . " pendente" . ($total > 1 ? 's' : '')
            . '. ' . ($lista ? "Alguns: {$lista}{$resto}" : '')
            . ' Quer que eu detalhe ou marque algum como feito?',
        'consulta' => 'contar_pendentes',
        'dados' => ['total' => $total, 'amostra' => $itens],
    ];
}

function iaConsultaListarPendentes(mysqli $mysqli, int $propriedade_id): array
{
    $itens = iaListarPendentesResumo($mysqli, $propriedade_id, 20);

    if (!$itens) {
        return [
            'ok' => true,
            'executado' => true,
            'msg' => 'Não há manejos pendentes. Tudo em dia por aqui!',
            'consulta' => 'listar_pendentes',
            'dados' => ['pendentes' => []],
        ];
    }

    $linhas = array_map(static fn ($i) => iaFormatarLinhaManejo($i), $itens);
    $msg = count($itens) . ' pendente' . (count($itens) > 1 ? 's' : '') . ': ' . implode('; ', array_slice($linhas, 0, 6));
    if (count($itens) > 6) {
        $msg .= '; … e mais ' . (count($itens) - 6) . '.';
    }

    return [
        'ok' => true,
        'executado' => true,
        'msg' => $msg,
        'consulta' => 'listar_pendentes',
        'dados' => ['pendentes' => $itens],
    ];
}

function iaConsultaUltimaColheita(mysqli $mysqli, int $propriedade_id, array $intent, array $contexto): array
{
    $row = iaBuscarUltimoApontamento($mysqli, $propriedade_id, 'colheita');
    if (!$row) {
        return [
            'ok' => true,
            'executado' => true,
            'msg' => 'Ainda não encontrei nenhuma colheita registrada no caderno.',
            'consulta' => 'ultima_colheita',
            'dados' => null,
        ];
    }

    return [
        'ok' => true,
        'executado' => true,
        'msg' => iaFormatarRespostaUltimoManejo($row, 'colheita'),
        'consulta' => 'ultima_colheita',
        'dados' => $row,
    ];
}

function iaConsultaUltimoManejo(mysqli $mysqli, int $propriedade_id, array $intent, array $contexto): array
{
    $tipo = (string) ($intent['tipo'] ?? '');
    $row = iaBuscarUltimoApontamento($mysqli, $propriedade_id, $tipo !== '' ? $tipo : null);

    if (!$row) {
        $label = $tipo ? (iaTiposManejo()[$tipo] ?? $tipo) : 'manejo';
        return [
            'ok' => true,
            'executado' => true,
            'msg' => "Não achei nenhum registro de {$label} no caderno ainda.",
            'consulta' => 'ultimo_manejo',
            'dados' => null,
        ];
    }

    return [
        'ok' => true,
        'executado' => true,
        'msg' => iaFormatarRespostaUltimoManejo($row, (string) $row['tipo']),
        'consulta' => 'ultimo_manejo',
        'dados' => $row,
    ];
}

function iaConsultaResumoManejos(mysqli $mysqli, int $propriedade_id, string $periodo): array
{
    [$inicio, $fim] = iaIntervaloPeriodo($periodo);
    $labelPeriodo = iaLabelPeriodo($periodo);

    $stmt = $mysqli->prepare("
        SELECT tipo, COUNT(*) AS qtd
        FROM apontamentos
        WHERE propriedade_id = ? AND data BETWEEN ? AND ?
        GROUP BY tipo
        ORDER BY qtd DESC
    ");
    $stmt->bind_param('iss', $propriedade_id, $inicio, $fim);
    $stmt->execute();
    $res = $stmt->get_result();

    $grupos = [];
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $qtd = (int) $row['qtd'];
        $total += $qtd;
        $grupos[] = ['tipo' => $row['tipo'], 'qtd' => $qtd];
    }
    $stmt->close();

    if ($total === 0) {
        return [
            'ok' => true,
            'executado' => true,
            'msg' => "Não encontrei manejos registrados {$labelPeriodo}.",
            'consulta' => 'resumo_manejos',
            'dados' => ['total' => 0, 'grupos' => []],
        ];
    }

    $partes = [];
    foreach ($grupos as $g) {
        $nome = iaTiposManejo()[$g['tipo']] ?? ucfirst((string) $g['tipo']);
        $partes[] = $g['qtd'] . ' ' . mb_strtolower($nome) . ($g['qtd'] > 1 ? 's' : '');
    }

    return [
        'ok' => true,
        'executado' => true,
        'msg' => "{$labelPeriodo} você registrou {$total} manejo" . ($total > 1 ? 's' : '')
            . ': ' . implode(', ', $partes) . '.',
        'consulta' => 'resumo_manejos',
        'dados' => ['total' => $total, 'grupos' => $grupos, 'periodo' => $periodo],
    ];
}

function iaConsultaTotalColheita(mysqli $mysqli, int $propriedade_id, array $intent, array $contexto): array
{
    [$inicio, $fim] = iaIntervaloPeriodo((string) ($intent['periodo'] ?? '30_dias'));
    $labelPeriodo = iaLabelPeriodo((string) ($intent['periodo'] ?? '30_dias'));

    $stmt = $mysqli->prepare("
        SELECT unidade, SUM(quantidade) AS total, COUNT(*) AS registros
        FROM apontamentos
        WHERE propriedade_id = ? AND tipo = 'colheita' AND data BETWEEN ? AND ?
          AND quantidade IS NOT NULL AND quantidade > 0
        GROUP BY unidade
    ");
    $stmt->bind_param('iss', $propriedade_id, $inicio, $fim);
    $stmt->execute();
    $res = $stmt->get_result();

    $totais = [];
    $registros = 0;
    while ($row = $res->fetch_assoc()) {
        $registros += (int) $row['registros'];
        $un = (string) ($row['unidade'] ?: 'kg');
        $totais[] = iaFormatarQuantidade((float) $row['total'], $un);
    }
    $stmt->close();

    if (!$totais) {
        return [
            'ok' => true,
            'executado' => true,
            'msg' => "Não encontrei colheitas com quantidade registrada {$labelPeriodo}.",
            'consulta' => 'total_colheita',
            'dados' => null,
        ];
    }

    $msg = "{$labelPeriodo}, em {$registros} colheita" . ($registros > 1 ? 's' : '')
        . ', o total foi ' . implode(' e ', $totais) . '.';

    return [
        'ok' => true,
        'executado' => true,
        'msg' => $msg,
        'consulta' => 'total_colheita',
        'dados' => ['totais' => $totais, 'registros' => $registros],
    ];
}

function iaBuscarUltimoApontamento(mysqli $mysqli, int $propriedade_id, ?string $tipo = null): ?array
{
    $sql = "
        SELECT a.id, a.tipo, a.data, a.quantidade, a.unidade, a.status, a.observacoes,
               COALESCE(a.data_conclusao, a.data) AS data_ref
        FROM apontamentos a
        WHERE a.propriedade_id = ?
    ";
    $types = 'i';
    $params = [$propriedade_id];

    if ($tipo) {
        $sql .= ' AND a.tipo = ?';
        $types .= 's';
        $params[] = $tipo;
    }

    $sql .= ' ORDER BY a.data_ref DESC, a.id DESC LIMIT 1';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return iaEnriquecerApontamento($mysqli, $row);
}

function iaEnriquecerApontamento(mysqli $mysqli, array $row): array
{
    $id = (int) $row['id'];

    $stmt = $mysqli->prepare("
        SELECT GROUP_CONCAT(DISTINCT ar.nome SEPARATOR ', ') AS areas
        FROM apontamento_detalhes ad
        JOIN areas ar ON ar.id = ad.valor
        WHERE ad.apontamento_id = ? AND ad.campo = 'area_id'
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $areas = (string) ($stmt->get_result()->fetch_assoc()['areas'] ?? '');
    $stmt->close();

    $stmt = $mysqli->prepare("
        SELECT p.nome
        FROM apontamento_detalhes ad
        JOIN produtos p ON p.id = ad.valor
        WHERE ad.apontamento_id = ? AND (ad.campo = 'produto' OR ad.campo = 'produto_id')
        LIMIT 1
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $produto = (string) ($stmt->get_result()->fetch_assoc()['nome'] ?? '');
    $stmt->close();

    $row['areas'] = $areas;
    $row['produto'] = $produto;

    return $row;
}

function iaFormatarLinhaManejo(array $item): string
{
    $tipo = iaTiposManejo()[$item['tipo'] ?? ''] ?? ucfirst((string) ($item['tipo'] ?? 'manejo'));
    $data = iaFormatarDataConsulta((string) ($item['data'] ?? ''));
    $area = trim((string) ($item['areas'] ?? ''));
    $prod = trim((string) ($item['produto'] ?? ''));

    $partes = [$tipo];
    if ($prod !== '') {
        $partes[] = $prod;
    }
    if ($area !== '') {
        $partes[] = 'em ' . $area;
    }
    $partes[] = $data;

    return implode(' ', $partes);
}

function iaFormatarRespostaUltimoManejo(array $row, string $contextoTipo): string
{
    $tipo = iaTiposManejo()[$row['tipo'] ?? ''] ?? ucfirst((string) ($row['tipo'] ?? 'manejo'));
    $data = iaFormatarDataConsulta((string) ($row['data'] ?? ''));
    $area = trim((string) ($row['areas'] ?? ''));
    $prod = trim((string) ($row['produto'] ?? ''));
    $qtd = $row['quantidade'] ?? null;
    $un = (string) ($row['unidade'] ?? '');

    if ($contextoTipo === 'colheita' && $qtd !== null && is_numeric($qtd) && (float) $qtd > 0) {
        $msg = 'Na última colheita, em ' . $data . ', você registrou '
            . iaFormatarQuantidade((float) $qtd, $un ?: 'kg');
        if ($prod !== '') {
            $msg .= ' de ' . $prod;
        }
        if ($area !== '') {
            $msg .= ' na ' . $area;
        }
        return rtrim($msg, '.') . '.';
    }

    $msg = 'O último registro de ' . mb_strtolower($tipo) . ' foi em ' . $data;
    if ($prod !== '') {
        $msg .= ', ' . $prod;
    }
    if ($area !== '') {
        $msg .= ', na ' . $area;
    }
    if ($qtd !== null && is_numeric($qtd) && (float) $qtd > 0) {
        $msg .= ', ' . iaFormatarQuantidade((float) $qtd, $un);
    }
    if (($row['status'] ?? '') === 'pendente') {
        $msg .= ' — ainda está pendente';
    }

    return rtrim($msg, '.') . '.';
}

function iaFormatarQuantidade(float $qtd, string $unidade): string
{
    $fmt = fmod($qtd, 1.0) === 0.0 ? (string) (int) $qtd : rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',');
    return trim($fmt . ' ' . $unidade);
}

function iaFormatarDataConsulta(string $data): string
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

function iaLabelPeriodo(string $periodo): string
{
    return match ($periodo) {
        'semana' => 'Nesta semana',
        'mes' => 'Neste mês',
        '7_dias' => 'Nos últimos 7 dias',
        'ano' => 'Neste ano',
        default => 'Nos últimos 30 dias',
    };
}

/** Resumo leve enviado ao GPT para dar noção do caderno. */
function iaResumoRapidoPropriedade(mysqli $mysqli, int $propriedade_id): array
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS n FROM apontamentos WHERE propriedade_id = ? AND status = 'pendente'");
    $stmt->bind_param('i', $propriedade_id);
    $stmt->execute();
    $pendentes = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
    $stmt->close();

    $ultima = iaBuscarUltimoApontamento($mysqli, $propriedade_id, 'colheita');
    $ultimaColheita = null;
    if ($ultima && !empty($ultima['quantidade'])) {
        $ultimaColheita = iaFormatarQuantidade((float) $ultima['quantidade'], (string) ($ultima['unidade'] ?: 'kg'))
            . ' em ' . iaFormatarDataConsulta((string) $ultima['data']);
    }

    return [
        'pendentes' => $pendentes,
        'ultima_colheita' => $ultimaColheita,
    ];
}
