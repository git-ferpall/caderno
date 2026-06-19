<?php
declare(strict_types=1);

require_once __DIR__ . '/carencia.php';
require_once __DIR__ . '/agrofit.php';
require_once __DIR__ . '/csfi.php';
require_once __DIR__ . '/clima.php';
require_once __DIR__ . '/lote.php';

function fsNiveisScore(): array
{
    return [
        'VERDE' => ['label' => 'Conforme', 'cor' => '#2e7d32'],
        'AMARELO' => ['label' => 'Atenção', 'cor' => '#f9a825'],
        'VERMELHO' => ['label' => 'Risco', 'cor' => '#c62828'],
        'CINZA' => ['label' => 'Sem dados', 'cor' => '#757575'],
    ];
}

function fsTabelaValidacaoExiste(mysqli $mysqli): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $mysqli->query("SHOW TABLES LIKE 'fitossanitaria_validacao'");
    $cache = $res && $res->num_rows > 0;
    return $cache;
}

function fsBuscarArea(mysqli $mysqli, int $userId, int $propriedadeId, int $areaId): ?array
{
    $stmt = $mysqli->prepare('
        SELECT id, nome, tipo, tamanho
        FROM areas
        WHERE id = ? AND user_id = ? AND propriedade_id = ?
        LIMIT 1
    ');
    $stmt->bind_param('iii', $areaId, $userId, $propriedadeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** @return array<int, array<string, mixed>> */
function fsBuscarAplicacoesDefensivosArea(
    mysqli $mysqli,
    int $propriedadeId,
    int $areaId,
    int $limite = 15,
    int $diasHistorico = 365
): array {
    if (!fsColunasCarenciaExistem($mysqli, 'herbicidas')) {
        return [];
    }

    $desde = date('Y-m-d', strtotime("-{$diasHistorico} days"));
    $tipos = fsTiposComCarencia();
    $tiposSql = "'" . implode("','", $tipos) . "'";

    $sql = "
        SELECT
            a.id,
            a.tipo,
            a.data AS data_aplicacao,
            a.status,
            MAX(CASE WHEN ad.campo IN ('herbicida','fungicida','inseticida') THEN ad.valor END) AS produto,
            MAX(CASE WHEN ad.campo = 'carencia_dias' THEN ad.valor END) AS carencia_dias,
            MAX(CASE WHEN ad.campo = 'data_liberacao_colheita' THEN ad.valor END) AS data_liberacao,
            MAX(CASE WHEN ad.campo = 'ingrediente_ativo' THEN ad.valor END) AS ingrediente_ativo
        FROM apontamentos a
        INNER JOIN apontamento_detalhes ada
            ON ada.apontamento_id = a.id AND ada.campo = 'area_id' AND ada.valor = ?
        INNER JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id
        WHERE a.propriedade_id = ?
          AND a.tipo IN ({$tiposSql})
          AND a.data >= ?
        GROUP BY a.id, a.tipo, a.data, a.status
        ORDER BY a.data DESC, a.id DESC
        LIMIT ?
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iisi', $areaId, $propriedadeId, $desde, $limite);
    $stmt->execute();
    $res = $stmt->get_result();

    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $lista[] = [
            'apontamento_id' => (int) $row['id'],
            'tipo' => (string) $row['tipo'],
            'produto' => (string) ($row['produto'] ?? ''),
            'data_aplicacao' => (string) $row['data_aplicacao'],
            'status' => (string) ($row['status'] ?? ''),
            'carencia_dias' => isset($row['carencia_dias']) ? (int) $row['carencia_dias'] : null,
            'data_liberacao' => (string) ($row['data_liberacao'] ?? ''),
            'ingrediente_ativo' => (string) ($row['ingrediente_ativo'] ?? ''),
        ];
    }
    $stmt->close();

    return $lista;
}

/** @return array<int, array<string, mixed>> */
function fsBuscarColheitasPendentesArea(mysqli $mysqli, int $propriedadeId, int $areaId): array
{
    $sql = "
        SELECT a.id, a.data, a.status
        FROM apontamentos a
        INNER JOIN apontamento_detalhes ada
            ON ada.apontamento_id = a.id AND ada.campo = 'area_id' AND ada.valor = ?
        WHERE a.propriedade_id = ?
          AND a.tipo = 'colheita'
          AND a.status = 'pendente'
        ORDER BY a.data ASC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $areaId, $propriedadeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $lista[] = [
            'apontamento_id' => (int) $row['id'],
            'data' => (string) $row['data'],
            'status' => (string) $row['status'],
        ];
    }
    $stmt->close();
    return $lista;
}

/** @return string[] */
function fsBuscarProdutosArea(mysqli $mysqli, int $propriedadeId, int $areaId): array
{
    $sql = "
        SELECT DISTINCT p.nome
        FROM apontamentos a
        INNER JOIN apontamento_detalhes ada
            ON ada.apontamento_id = a.id AND ada.campo = 'area_id' AND ada.valor = ?
        INNER JOIN apontamento_detalhes adp
            ON adp.apontamento_id = a.id AND adp.campo IN ('produto_id', 'produto')
        LEFT JOIN produtos p ON p.id = CAST(adp.valor AS UNSIGNED)
        WHERE a.propriedade_id = ?
          AND p.nome IS NOT NULL AND p.nome <> ''
        ORDER BY p.nome ASC
        LIMIT 10
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $areaId, $propriedadeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $nomes = [];
    while ($row = $res->fetch_assoc()) {
        $nomes[] = (string) $row['nome'];
    }
    $stmt->close();
    return $nomes;
}

function fsBuscarUltimaValidacao(mysqli $mysqli, int $propriedadeId, int $areaId): ?array
{
    if (!fsTabelaValidacaoExiste($mysqli)) {
        return null;
    }

    $stmt = $mysqli->prepare('
        SELECT id, texto, criado_em, user_id
        FROM fitossanitaria_validacao
        WHERE propriedade_id = ? AND area_id = ?
        ORDER BY criado_em DESC
        LIMIT 1
    ');
    $stmt->bind_param('ii', $propriedadeId, $areaId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'texto' => (string) $row['texto'],
        'criado_em' => (string) $row['criado_em'],
        'user_id' => (int) $row['user_id'],
    ];
}

/**
 * Calcula score fitossanitário de uma área.
 *
 * @return array{nivel: string, label: string, cor: string, explicacao: string, motivos: string[]}
 */
function fsCalcularScoreArea(mysqli $mysqli, int $propriedadeId, int $areaId, ?string $dataRef = null): array
{
    $hoje = $dataRef ?: date('Y-m-d');
    $niveis = fsNiveisScore();
    $motivos = [];
    $nivel = 'CINZA';

    $aplicacoes = fsBuscarAplicacoesDefensivosArea($mysqli, $propriedadeId, $areaId, 50);
    $colheitasPendentes = fsBuscarColheitasPendentesArea($mysqli, $propriedadeId, $areaId);

    if (!$aplicacoes && !fsColunasCarenciaExistem($mysqli, 'herbicidas')) {
        return [
            'nivel' => 'CINZA',
            'label' => $niveis['CINZA']['label'],
            'cor' => $niveis['CINZA']['cor'],
            'explicacao' => 'Cadastro de carência ainda não disponível no servidor. Execute a migration da Fase 1.',
            'motivos' => ['Migration fitossanitaria_fase1 pendente'],
        ];
    }

    if (!$aplicacoes) {
        return [
            'nivel' => 'CINZA',
            'label' => $niveis['CINZA']['label'],
            'cor' => $niveis['CINZA']['cor'],
            'explicacao' => 'Sem aplicações de defensivos registradas nesta área nos últimos 12 meses.',
            'motivos' => ['Nenhum histórico fitossanitário recente'],
        ];
    }

    $violacaoHoje = fsValidarColheitaCarencia($mysqli, $propriedadeId, [$areaId], $hoje);
    if (!empty($violacaoHoje['violacoes'])) {
        $nivel = 'VERMELHO';
        foreach ($violacaoHoje['violacoes'] as $v) {
            $motivos[] = sprintf(
                'Carência ativa: %s — colheita só liberada em %s',
                $v['produto'] ?: ucfirst((string) $v['tipo']),
                fsFormatarDataBr((string) $v['data_liberacao'])
            );
        }
    }

    foreach ($colheitasPendentes as $col) {
        $dataCol = (string) $col['data'];
        $check = fsValidarColheitaCarencia($mysqli, $propriedadeId, [$areaId], $dataCol);
        if (!empty($check['violacoes'])) {
            $nivel = 'VERMELHO';
            foreach ($check['violacoes'] as $v) {
                $motivos[] = sprintf(
                    'Colheita pendente em %s viola carência de %s (liberação %s)',
                    fsFormatarDataBr($dataCol),
                    $v['produto'] ?: ucfirst((string) $v['tipo']),
                    fsFormatarDataBr((string) $v['data_liberacao'])
                );
            }
        }
    }

    $semCarencia = [];
    $carenciaAtiva = [];
    $carenciaProxima = [];

    foreach ($aplicacoes as $app) {
        $lib = (string) ($app['data_liberacao'] ?? '');
        $carencia = $app['carencia_dias'];

        if ($carencia === null || $carencia <= 0) {
            $semCarencia[] = $app['produto'] ?: ucfirst((string) $app['tipo']);
            continue;
        }

        if ($lib !== '' && $lib > $hoje) {
            $diasRest = (int) floor((strtotime($lib) - strtotime($hoje)) / 86400);
            $carenciaAtiva[] = [
                'produto' => $app['produto'],
                'liberacao' => $lib,
                'dias' => $diasRest,
            ];
            if ($diasRest <= 7 && $nivel !== 'VERMELHO') {
                $carenciaProxima[] = $app['produto'] ?: ucfirst((string) $app['tipo']);
            }
        }
    }

    if ($nivel !== 'VERMELHO') {
        if ($semCarencia) {
            $nivel = 'AMARELO';
            $unicos = array_unique($semCarencia);
            $motivos[] = 'Produto(s) sem carência cadastrada: ' . implode(', ', array_slice($unicos, 0, 3));
        }

        if ($carenciaAtiva) {
            if ($nivel !== 'AMARELO') {
                $nivel = 'AMARELO';
            }
            $primeira = $carenciaAtiva[0];
            $motivos[] = sprintf(
                'Intervalo de segurança ativo até %s (%d dia(s) restante(s))',
                fsFormatarDataBr((string) $primeira['liberacao']),
                (int) $primeira['dias']
            );
        }

        if ($carenciaProxima && $nivel === 'AMARELO') {
            $motivos[] = 'Liberação para colheita em até 7 dias — planeje a operação';
        }
    }

    if ($nivel === 'CINZA' || ($nivel !== 'VERMELHO' && $nivel !== 'AMARELO')) {
        $nivel = 'VERDE';
        $motivos = ['Nenhuma violação de carência identificada para a data de referência'];
        if ($carenciaAtiva) {
            $ultima = $carenciaAtiva[0];
            $motivos[] = sprintf(
                'Próxima liberação: %s (%s)',
                fsFormatarDataBr((string) $ultima['liberacao']),
                $ultima['produto'] ?: 'defensivo'
            );
        } else {
            $motivos[] = 'Área sem carência ativa no momento';
        }
    }

    $meta = $niveis[$nivel] ?? $niveis['CINZA'];
    $explicacao = match ($nivel) {
        'VERMELHO' => 'Há risco técnico ou legal: colheita ou operação pode violar o intervalo de segurança.',
        'AMARELO' => 'Situação requer atenção: carência ativa, dados incompletos ou liberação próxima.',
        'VERDE' => 'Situação conforme com base nos registros de aplicação e carência.',
        default => 'Informações insuficientes para avaliação completa.',
    };

    return [
        'nivel' => $nivel,
        'label' => $meta['label'],
        'cor' => $meta['cor'],
        'explicacao' => $explicacao,
        'motivos' => array_values(array_unique($motivos)),
    ];
}

function fsGerarRecomendacao(array $score, array $aplicacoes, array $colheitasPendentes): string
{
    $nivel = $score['nivel'] ?? 'CINZA';

    if ($nivel === 'VERMELHO') {
        return 'Não programe colheita nesta área até a data de liberação. Revise o último defensivo aplicado e registre justificativa técnica se houver exceção autorizada.';
    }
    if ($nivel === 'AMARELO') {
        if ($colheitasPendentes) {
            return 'Confira as datas das colheitas pendentes antes de executar. Cadastre carência nos produtos que ainda não possuem intervalo de segurança.';
        }
        return 'Monitore o intervalo de segurança e mantenha o cadastro de carência dos defensivos atualizado.';
    }
    if ($nivel === 'VERDE') {
        return 'Continue registrando aplicações com produto, dose e data. O painel usa esses dados para calcular liberação de colheita.';
    }
    return 'Registre aplicações de defensivos nesta área para habilitar análise de carência e score.';
}

function fsGerarAcaoSugerida(array $score, array $aplicacoes): string
{
    return match ($score['nivel'] ?? 'CINZA') {
        'VERMELHO' => 'Adiar colheita ou solicitar validação do agrônomo responsável.',
        'AMARELO' => empty(array_filter($aplicacoes, fn ($a) => !empty($a['carencia_dias'])))
            ? 'Cadastrar carência nos defensivos usados nesta área.'
            : 'Aguardar liberação ou registrar nova aplicação somente após orientação técnica.',
        'VERDE' => 'Manter monitoramento e registrar próximas aplicações no caderno.',
        default => 'Registrar primeira aplicação fitossanitária com área e produto.',
    };
}

function fsMontarPainelArea(mysqli $mysqli, int $userId, int $propriedadeId, int $areaId): array
{
    $area = fsBuscarArea($mysqli, $userId, $propriedadeId, $areaId);
    if (!$area) {
        return ['ok' => false, 'msg' => 'Área não encontrada'];
    }

    $hoje = date('Y-m-d');
    $aplicacoes = fsBuscarAplicacoesDefensivosArea($mysqli, $propriedadeId, $areaId);
    $colheitasPendentes = fsBuscarColheitasPendentesArea($mysqli, $propriedadeId, $areaId);
    $produtos = fsBuscarProdutosArea($mysqli, $propriedadeId, $areaId);
    $score = fsCalcularScoreArea($mysqli, $propriedadeId, $areaId, $hoje);
    $validacao = fsBuscarUltimaValidacao($mysqli, $propriedadeId, $areaId);

    $carenciasAtivas = [];
    foreach ($aplicacoes as $app) {
        $lib = (string) ($app['data_liberacao'] ?? '');
        if ($lib !== '' && $lib > $hoje) {
            $carenciasAtivas[] = [
                'produto' => $app['produto'],
                'tipo' => $app['tipo'],
                'data_aplicacao' => $app['data_aplicacao'],
                'data_liberacao' => $lib,
                'carencia_dias' => $app['carencia_dias'],
                'dias_restantes' => max(0, (int) floor((strtotime($lib) - strtotime($hoje)) / 86400)),
            ];
        }
    }

    $semCarencia = array_values(array_unique(array_filter(array_map(
        fn ($a) => empty($a['carencia_dias']) ? ($a['produto'] ?: ucfirst((string) $a['tipo'])) : null,
        $aplicacoes
    ))));

    $diagnostico = sprintf(
        'Área %s (%s): score %s. %s',
        $area['nome'],
        $area['tipo'] ?? 'talhão',
        $score['label'],
        $score['explicacao']
    );

    $climaRegistros = fsBuscarRegistrosClimaticos($mysqli, $propriedadeId);
    $clima = fsAvaliarClimaAplicacao($climaRegistros);
    $csfi = fsVerificarCsfiCulturas($mysqli, $produtos);
    $agrofit = fsVerificarAplicacoesAgrofit($mysqli, $aplicacoes, $produtos);

    $recomendacao = fsGerarRecomendacao($score, $aplicacoes, $colheitasPendentes);
    $acaoSugerida = fsGerarAcaoSugerida($score, $aplicacoes);

    if (!empty($csfi['csfi'])) {
        $acaoSugerida .= ' Cultura CSFI: validação do agrônomo obrigatória.';
    }
    if (($clima['aplicacao_recomendada'] ?? null) === false) {
        $recomendacao = ($clima['recomendacao'] ?? '') . ' ' . $recomendacao;
    }
    if (!empty($agrofit['alertas'])) {
        $recomendacao .= ' Revise registro MAPA/AGROFIT dos produtos aplicados.';
    }

    $painelBase = [
        'ok' => true,
        'area' => [
            'id' => (int) $area['id'],
            'nome' => (string) $area['nome'],
            'tipo' => (string) ($area['tipo'] ?? ''),
            'tamanho' => $area['tamanho'] ?? null,
        ],
        'propriedade_id' => $propriedadeId,
        'data_referencia' => $hoje,
        'score' => $score,
        'diagnostico' => $diagnostico,
        'risco_fitossanitario' => [
            'nivel' => count($aplicacoes) >= 3 ? 'moderado' : (count($aplicacoes) ? 'baixo' : 'indeterminado'),
            'aplicacoes_12m' => count($aplicacoes),
            'sem_carencia_cadastrada' => $semCarencia,
            'resumo' => count($aplicacoes)
                ? count($aplicacoes) . ' aplicação(ões) nos últimos 12 meses.'
                : 'Sem aplicações recentes registradas.',
        ],
        'risco_residuo' => [
            'nivel' => match ($score['nivel']) {
                'VERMELHO' => 'alto',
                'AMARELO' => 'moderado',
                default => 'baixo',
            },
            'carencias_ativas' => count($carenciasAtivas),
            'resumo' => $carenciasAtivas
                ? count($carenciasAtivas) . ' carência(s) ativa(s) na área.'
                : 'Nenhuma carência bloqueando colheita hoje.',
        ],
        'status_carencia' => [
            'ativas' => $carenciasAtivas,
            'colheitas_pendentes' => $colheitasPendentes,
        ],
        'produto_ia' => array_values(array_unique(array_filter(array_map(
            fn ($a) => $a['ingrediente_ativo'] ?: null,
            $aplicacoes
        )))),
        'cultura' => $produtos,
        'cultura_autorizada' => $agrofit,
        'agrofit' => $agrofit,
        'csfi' => $csfi,
        'clima' => $clima,
        'historico' => $aplicacoes,
        'recomendacao' => trim($recomendacao),
        'acao_sugerida' => $acaoSugerida,
        'validacao_agronomo' => $validacao,
        'aviso_legal' => 'A IA Fitossanitária Frutag é ferramenta de apoio à decisão. '
            . 'Validação do responsável técnico habilitado é obrigatória para decisões críticas.',
    ];

    $painelBase['lote'] = fsObterOuAtualizarLote($mysqli, $propriedadeId, $areaId, $painelBase);

    return $painelBase;
}

/** @return array<int, array<string, mixed>> */
function fsListarScoresAreas(mysqli $mysqli, int $userId, int $propriedadeId): array
{
    $stmt = $mysqli->prepare('
        SELECT id, nome, tipo FROM areas
        WHERE user_id = ? AND propriedade_id = ?
        ORDER BY nome ASC
    ');
    $stmt->bind_param('ii', $userId, $propriedadeId);
    $stmt->execute();
    $res = $stmt->get_result();

    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $areaId = (int) $row['id'];
        $score = fsCalcularScoreArea($mysqli, $propriedadeId, $areaId);
        $lista[] = [
            'id' => $areaId,
            'nome' => (string) $row['nome'],
            'tipo' => (string) ($row['tipo'] ?? ''),
            'score' => $score,
        ];
    }
    $stmt->close();

    return $lista;
}

function fsResponderPerguntaFitossanitaria(mysqli $mysqli, array $painel, string $pergunta): array
{
    $pergunta = trim($pergunta);
    if ($pergunta === '') {
        return ['ok' => false, 'msg' => 'Digite uma pergunta.'];
    }

    $q = mb_strtolower($pergunta, 'UTF-8');
    $area = $painel['area'] ?? [];
    $score = $painel['score'] ?? [];
    $carencias = $painel['status_carencia']['ativas'] ?? [];

    if (preg_match('/liberad|posso colher|colher hoje|colheita/i', $q)) {
        $nivel = $score['nivel'] ?? 'CINZA';
        if ($nivel === 'VERMELHO') {
            $texto = 'Não. ' . implode(' ', $score['motivos'] ?? []);
        } elseif ($nivel === 'AMARELO' && $carencias) {
            $c = $carencias[0];
            $texto = sprintf(
                'Atenção: há carência ativa até %s (%s). Colheita hoje pode não estar liberada.',
                fsFormatarDataBr((string) $c['data_liberacao']),
                $c['produto'] ?: 'defensivo'
            );
        } else {
            $texto = 'Com base nos registros, não há bloqueio de carência para hoje nesta área. Confirme no campo antes de colher.';
        }
        return ['ok' => true, 'resposta' => $texto, 'fonte' => 'regras'];
    }

    if (preg_match('/carência|carencia|intervalo|segurança|seguranca/i', $q)) {
        if (!$carencias) {
            return ['ok' => true, 'resposta' => 'Nenhuma carência ativa registrada para esta área no momento.', 'fonte' => 'regras'];
        }
        $partes = [];
        foreach (array_slice($carencias, 0, 5) as $c) {
            $partes[] = sprintf(
                '%s: liberação em %s (%d dia(s) restante(s))',
                $c['produto'] ?: 'Defensivo',
                fsFormatarDataBr((string) $c['data_liberacao']),
                (int) $c['dias_restantes']
            );
        }
        return ['ok' => true, 'resposta' => implode('; ', $partes), 'fonte' => 'regras'];
    }

    if (preg_match('/score|risco|status|situação|situacao/i', $q)) {
        return [
            'ok' => true,
            'resposta' => ($score['label'] ?? 'Sem dados') . ' — ' . ($score['explicacao'] ?? ''),
            'fonte' => 'regras',
        ];
    }

    if (preg_match('/recomend|o que fazer|próximo|proximo/i', $q)) {
        return [
            'ok' => true,
            'resposta' => ($painel['recomendacao'] ?? '') . ' ' . ($painel['acao_sugerida'] ?? ''),
            'fonte' => 'regras',
        ];
    }

    if (preg_match('/cultura|produto cultivado/i', $q)) {
        $culturas = $painel['cultura'] ?? [];
        $texto = $culturas
            ? 'Culturas/produtos registrados nesta área: ' . implode(', ', $culturas) . '.'
            : 'Nenhum produto cultivado vinculado a esta área nos apontamentos.';
        return ['ok' => true, 'resposta' => $texto, 'fonte' => 'regras'];
    }

    if (preg_match('/aplicar|posso aplicar|defensivo hoje|clima|chuva|vento/i', $q)) {
        $clima = $painel['clima'] ?? [];
        $texto = ($clima['recomendacao'] ?? '') . ' ' . ($clima['resumo'] ?? '');
        return ['ok' => true, 'resposta' => trim($texto) ?: 'Consulte registros climáticos no caderno.', 'fonte' => 'regras'];
    }

    if (preg_match('/csfi|minor crop|minor/i', $q)) {
        $csfi = $painel['csfi'] ?? [];
        return ['ok' => true, 'resposta' => $csfi['resumo'] ?? 'Cultura não verificada como CSFI.', 'fonte' => 'regras'];
    }

    if (preg_match('/lote|liberado|mercado|auditoria|hash/i', $q)) {
        $lote = $painel['lote'] ?? null;
        if (!$lote) {
            return ['ok' => true, 'resposta' => 'Lote Frutag ainda não gerado para esta área.', 'fonte' => 'regras'];
        }
        return [
            'ok' => true,
            'resposta' => sprintf(
                'Lote %s — status %s (score %s). Hash: %s',
                $lote['codigo_lote'],
                $lote['status_label'] ?? $lote['status_lote'],
                $lote['score_nivel'] ?? '',
                $lote['hash_curto'] ?? substr((string) ($lote['hash_auditoria'] ?? ''), 0, 12)
            ),
            'fonte' => 'regras',
        ];
    }

    if (preg_match('/agrofit|mapa|registro|cultura autorizada/i', $q)) {
        $ag = $painel['agrofit'] ?? [];
        return ['ok' => true, 'resposta' => $ag['resumo'] ?? 'Consulte catálogo AGROFIT local.', 'fonte' => 'regras'];
    }

    return ['ok' => false, 'msg' => 'perguntar_ia', 'pergunta' => $pergunta];
}

function fsPerguntarComGpt(array $painel, string $pergunta): array
{
    require_once __DIR__ . '/../ia/ia_helpers.php';

    $model = iaModel('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
    $contexto = json_encode([
        'area' => $painel['area'] ?? null,
        'score' => $painel['score'] ?? null,
        'carencias' => $painel['status_carencia'] ?? null,
        'historico' => array_slice($painel['historico'] ?? [], 0, 8),
        'recomendacao' => $painel['recomendacao'] ?? null,
    ], JSON_UNESCAPED_UNICODE);

    $system = <<<PROMPT
Você é assistente fitossanitário do Caderno de Campo Frutag.
Responda em português brasileiro, de forma objetiva (2-4 frases).
Use APENAS os dados JSON fornecidos. Se faltar informação, diga claramente.
Não invente dados. Use clima, CSFI, AGROFIT e lote Frutag quando presentes no JSON.
PROMPT;

    $resp = iaOpenAiRequest('/chat/completions', [
        'model' => $model,
        'temperature' => 0.3,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Dados do painel:\n{$contexto}\n\nPergunta do produtor: {$pergunta}"],
        ],
    ]);

    $texto = trim((string) ($resp['choices'][0]['message']['content'] ?? ''));
    if ($texto === '') {
        throw new RuntimeException('Resposta vazia da IA.');
    }

    return ['ok' => true, 'resposta' => $texto, 'fonte' => 'gpt'];
}

/**
 * Processa pergunta do usuário sobre uma área (regras + GPT).
 */
function fsProcessarPerguntaArea(mysqli $mysqli, int $userId, int $areaId, string $pergunta): array
{
    $pergunta = trim($pergunta);
    if ($areaId <= 0 || $pergunta === '') {
        return ['ok' => false, 'msg' => 'Informe área e pergunta.'];
    }

    require_once __DIR__ . '/../apontamento_arquivos.php';

    $prop = obterPropriedadeAtiva($mysqli, $userId);
    if (!$prop) {
        return ['ok' => false, 'msg' => 'Nenhuma propriedade ativa'];
    }

    $painel = fsMontarPainelArea($mysqli, $userId, (int) $prop['id'], $areaId);
    if (empty($painel['ok'])) {
        return $painel;
    }

    $resultado = fsResponderPerguntaFitossanitaria($mysqli, $painel, $pergunta);

    if (!empty($resultado['ok'])) {
        $resultado['transcricao'] = $pergunta;
        return $resultado;
    }

    if (($resultado['msg'] ?? '') === 'perguntar_ia') {
        try {
            $gpt = fsPerguntarComGpt($painel, $pergunta);
            $gpt['transcricao'] = $pergunta;
            return $gpt;
        } catch (Throwable $e) {
            return [
                'ok' => true,
                'resposta' => 'Não encontrei uma regra específica. '
                    . ($painel['recomendacao'] ?? 'Consulte o agrônomo responsável.'),
                'fonte' => 'fallback',
                'transcricao' => $pergunta,
            ];
        }
    }

    return $resultado;
}
