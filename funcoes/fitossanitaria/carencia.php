<?php
declare(strict_types=1);

/** Tipos de aplicação com intervalo de segurança (carência). */
function fsTiposComCarencia(): array
{
    return ['herbicida', 'fungicida', 'inseticida'];
}

function fsCampoDetalheInsumo(string $tipo): string
{
    return match ($tipo) {
        'herbicida' => 'herbicida',
        'fungicida' => 'fungicida',
        'inseticida' => 'inseticida',
        default => 'insumo',
    };
}

function fsTabelaCatalogoInsumo(string $tipo): ?array
{
    return match ($tipo) {
        'herbicida' => ['tabela' => 'herbicidas', 'ativo' => "status = 'ativo'"],
        'fungicida' => ['tabela' => 'fungicidas', 'ativo' => 'ativo = 1'],
        'inseticida' => ['tabela' => 'inseticidas', 'ativo' => 'ativo = 1'],
        default => null,
    };
}

function fsColunasCarenciaExistem(mysqli $mysqli, string $tabela): bool
{
    static $cache = [];
    if (isset($cache[$tabela])) {
        return $cache[$tabela];
    }
    $tabela = preg_replace('/[^a-z_]/', '', $tabela) ?? $tabela;
    $res = $mysqli->query("SHOW COLUMNS FROM `{$tabela}` LIKE 'carencia_dias'");
    $cache[$tabela] = $res && $res->num_rows > 0;
    return $cache[$tabela];
}

/** Busca carência e ingrediente ativo no catálogo (id numérico ou nome). */
function fsBuscarDadosInsumo(mysqli $mysqli, string $tipo, string $valor): ?array
{
    $cat = fsTabelaCatalogoInsumo($tipo);
    if (!$cat || trim($valor) === '') {
        return null;
    }

    $tabela = $cat['tabela'];
    if (!fsColunasCarenciaExistem($mysqli, $tabela)) {
        return null;
    }

    $cols = 'id, nome, carencia_dias, ingrediente_ativo';
    if (ctype_digit($valor)) {
        $stmt = $mysqli->prepare("SELECT {$cols} FROM `{$tabela}` WHERE id = ? AND {$cat['ativo']} LIMIT 1");
        $id = (int) $valor;
        $stmt->bind_param('i', $id);
    } else {
        $nome = trim($valor);
        $stmt = $mysqli->prepare("SELECT {$cols} FROM `{$tabela}` WHERE nome = ? AND {$cat['ativo']} LIMIT 1");
        $stmt->bind_param('s', $nome);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'nome' => (string) ($row['nome'] ?? ''),
        'carencia_dias' => isset($row['carencia_dias']) ? (int) $row['carencia_dias'] : null,
        'ingrediente_ativo' => trim((string) ($row['ingrediente_ativo'] ?? '')),
    ];
}

function fsCalcularDataLiberacao(string $dataAplicacao, int $carenciaDias): string
{
    $ts = strtotime($dataAplicacao . ' +' . max(0, $carenciaDias) . ' days');
    return $ts ? date('Y-m-d', $ts) : $dataAplicacao;
}

function fsFormatarDataBr(string $data): string
{
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $data;
}

function fsInserirDetalheFitossanitario(mysqli $mysqli, int $apontamentoId, string $campo, string $valor): void
{
    $stmt = $mysqli->prepare('
        INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)
    ');
    $stmt->bind_param('iss', $apontamentoId, $campo, $valor);
    $stmt->execute();
    $stmt->close();
}

/**
 * Grava carência no apontamento após salvar defensivo.
 * @return array|null dados da liberação ou null se sem carência cadastrada
 */
function fsAplicarCarenciaDefensivo(
    mysqli $mysqli,
    int $apontamentoId,
    string $tipo,
    string $insumoValor,
    string $dataAplicacao
): ?array {
    $dados = fsBuscarDadosInsumo($mysqli, $tipo, $insumoValor);
    if (!$dados || empty($dados['carencia_dias']) || (int) $dados['carencia_dias'] <= 0) {
        return null;
    }

    $carencia = (int) $dados['carencia_dias'];
    $liberacao = fsCalcularDataLiberacao($dataAplicacao, $carencia);

    fsInserirDetalheFitossanitario($mysqli, $apontamentoId, 'carencia_dias', (string) $carencia);
    fsInserirDetalheFitossanitario($mysqli, $apontamentoId, 'data_liberacao_colheita', $liberacao);

    if ($dados['ingrediente_ativo'] !== '') {
        fsInserirDetalheFitossanitario($mysqli, $apontamentoId, 'ingrediente_ativo', $dados['ingrediente_ativo']);
    }

    return [
        'produto' => $dados['nome'],
        'carencia_dias' => $carencia,
        'data_liberacao_colheita' => $liberacao,
        'ingrediente_ativo' => $dados['ingrediente_ativo'],
    ];
}

function fsMensagemSucessoCarencia(?array $carencia): string
{
    if (!$carencia) {
        return '';
    }
    return ' Colheita liberada a partir de '
        . fsFormatarDataBr((string) $carencia['data_liberacao_colheita'])
        . ' (carência de ' . (int) $carencia['carencia_dias'] . ' dias).';
}

/**
 * Verifica se a data de colheita viola carência em alguma das áreas.
 *
 * @param int[] $areaIds
 * @param int[] $produtoIds ignorado na fase 1 (carência por área)
 */
function fsValidarColheitaCarencia(
    mysqli $mysqli,
    int $propriedadeId,
    array $areaIds,
    string $dataColheita,
    array $produtoIds = []
): array {
    $areaIds = array_values(array_filter(array_map('intval', $areaIds)));
    if (!$areaIds) {
        return ['violacoes' => []];
    }

    if (!fsColunasCarenciaExistem($mysqli, 'herbicidas')) {
        return ['violacoes' => []];
    }

    $placeholders = implode(',', array_fill(0, count($areaIds), '?'));
    $tipos = fsTiposComCarencia();
    $tiposSql = "'" . implode("','", $tipos) . "'";

    $sql = "
        SELECT
            a.id,
            a.tipo,
            a.data AS data_aplicacao,
            ar.id AS area_id,
            ar.nome AS area_nome,
            MAX(CASE WHEN ad.campo = 'data_liberacao_colheita' THEN ad.valor END) AS data_liberacao,
            MAX(CASE WHEN ad.campo IN ('herbicida','fungicida','inseticida') THEN ad.valor END) AS produto,
            MAX(CASE WHEN ad.campo = 'carencia_dias' THEN ad.valor END) AS carencia_dias,
            MAX(CASE WHEN ad.campo = 'ingrediente_ativo' THEN ad.valor END) AS ingrediente_ativo
        FROM apontamentos a
        INNER JOIN apontamento_detalhes ada
            ON ada.apontamento_id = a.id AND ada.campo = 'area_id' AND ada.valor IN ({$placeholders})
        INNER JOIN areas ar ON ar.id = CAST(ada.valor AS UNSIGNED)
        INNER JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id
        WHERE a.propriedade_id = ?
          AND a.tipo IN ({$tiposSql})
        GROUP BY a.id, ar.id, ar.nome, a.tipo, a.data
        HAVING data_liberacao IS NOT NULL AND data_liberacao > ?
        ORDER BY data_liberacao DESC
    ";

    $types = str_repeat('i', count($areaIds)) . 'is';
    $params = array_merge($areaIds, [$propriedadeId, $dataColheita]);

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $violacoes = [];
    while ($row = $res->fetch_assoc()) {
        $lib = (string) ($row['data_liberacao'] ?? '');
        $diasRestantes = max(0, (int) floor((strtotime($lib) - strtotime($dataColheita)) / 86400));
        $violacoes[] = [
            'apontamento_id' => (int) $row['id'],
            'tipo' => (string) $row['tipo'],
            'produto' => (string) ($row['produto'] ?? ''),
            'area_id' => (int) $row['area_id'],
            'area_nome' => (string) ($row['area_nome'] ?? ''),
            'data_aplicacao' => (string) ($row['data_aplicacao'] ?? ''),
            'data_liberacao' => $lib,
            'carencia_dias' => (int) ($row['carencia_dias'] ?? 0),
            'ingrediente_ativo' => (string) ($row['ingrediente_ativo'] ?? ''),
            'dias_restantes' => $diasRestantes,
        ];
    }
    $stmt->close();

    return ['violacoes' => $violacoes];
}

function fsMensagemAlertaCarencia(array $violacoes): string
{
    if (!$violacoes) {
        return '';
    }
    $partes = [];
    foreach (array_slice($violacoes, 0, 3) as $v) {
        $partes[] = sprintf(
            '%s em %s: liberação só em %s (%d dia(s) de carência)',
            $v['produto'] ?: ucfirst((string) $v['tipo']),
            $v['area_nome'] ?: 'área',
            fsFormatarDataBr((string) $v['data_liberacao']),
            (int) $v['dias_restantes']
        );
    }
    $texto = implode('; ', $partes);
    if (count($violacoes) > 3) {
        $texto .= ' e outras.';
    }
    return 'Alerta de carência: a colheita está antes do intervalo de segurança. ' . $texto
        . ' Informe justificativa técnica para prosseguir ou ajuste a data.';
}

/** Valida colheita de um apontamento existente (conclusão por voz/UI). */
function fsValidarColheitaApontamentoId(mysqli $mysqli, int $apontamentoId, string $dataColheita): array
{
    $stmt = $mysqli->prepare('SELECT propriedade_id FROM apontamentos WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $apontamentoId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['violacoes' => []];
    }

    $stmt = $mysqli->prepare("
        SELECT valor FROM apontamento_detalhes WHERE apontamento_id = ? AND campo = 'area_id'
    ");
    $stmt->bind_param('i', $apontamentoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $areas = [];
    while ($r = $res->fetch_assoc()) {
        $areas[] = (int) $r['valor'];
    }
    $stmt->close();

    return fsValidarColheitaCarencia(
        $mysqli,
        (int) $row['propriedade_id'],
        $areas,
        $dataColheita
    );
}

function fsResponderAlertaCarenciaColheita(array $violacoes, bool $confirmar, string $justificativa): ?array
{
    if (!$violacoes) {
        return null;
    }
    if (!$confirmar || trim($justificativa) === '') {
        return [
            'ok' => false,
            'carencia_alerta' => true,
            'violacoes' => $violacoes,
            'msg' => fsMensagemAlertaCarencia($violacoes),
        ];
    }
    return null;
}
