<?php
declare(strict_types=1);

/**
 * Dados compartilhados — relatório de manejos (preview + PDF).
 */

function relatorioManejosUserId(): int
{
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        require_once __DIR__ . '/../../sso/verify_jwt.php';
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new RuntimeException('Usuário não autenticado.');
    }
    return (int) $user_id;
}

function relatorioManejosEstimarPaginas(int $concluidos, int $pendentes, int $atrasados): int
{
    $linhasPorPagina = 16;
    $paginas = 2;

    if ($concluidos > 0) {
        $paginas += max(1, (int) ceil($concluidos / $linhasPorPagina));
    }
    if ($pendentes > 0) {
        $paginas += max(1, (int) ceil($pendentes / $linhasPorPagina));
    }
    if ($atrasados > 0) {
        $paginas += max(1, (int) ceil($atrasados / $linhasPorPagina));
    }

    return max(1, $paginas);
}

/**
 * @return array{
 *   nomes_props: list<string>,
 *   concluidos: list<array<string,mixed>>,
 *   pendentes: list<array<string,mixed>>,
 *   atrasados: list<array<string,mixed>>,
 *   total_concluidos: int,
 *   total_pendentes: int,
 *   total_atrasados: int,
 *   total_geral: int,
 *   pct_concluidos: int,
 *   pct_pendentes: int,
 *   pct_atrasados: int,
 *   pct_emdia: int,
 *   data_ini: string,
 *   data_fim: string,
 *   paginas_estimadas: int
 * }
 */
function relatorioManejosCarregar(mysqli $mysqli, int $user_id, array $post): array
{
    $propriedades = $post['pfpropriedades'] ?? [];
    if (!is_array($propriedades)) {
        $propriedades = [$propriedades];
    }
    $propriedades = array_values(array_filter(array_map('intval', $propriedades)));

    $cultivo = trim((string) ($post['pfcult'] ?? ''));
    $area = trim((string) ($post['pfarea'] ?? ''));
    $manejo = trim((string) ($post['pfmane'] ?? ''));
    $data_ini = (string) ($post['pfini'] ?? date('Y-m-01'));
    $data_fim = (string) ($post['pffin'] ?? date('Y-m-t'));

    if (!$propriedades) {
        $stmtProp = $mysqli->prepare('SELECT id FROM propriedades WHERE user_id = ?');
        $stmtProp->bind_param('i', $user_id);
        $stmtProp->execute();
        $resProp = $stmtProp->get_result();
        while ($row = $resProp->fetch_assoc()) {
            $propriedades[] = (int) $row['id'];
        }
        $stmtProp->close();
    }

    if (!$propriedades) {
        throw new RuntimeException('Nenhuma propriedade encontrada para este usuário.');
    }

    $placeholdersProps = implode(',', array_fill(0, count($propriedades), '?'));
    $typesProps = str_repeat('i', count($propriedades));

    $stmtProps = $mysqli->prepare("SELECT nome_razao FROM propriedades WHERE id IN ($placeholdersProps)");
    $stmtProps->bind_param($typesProps, ...$propriedades);
    $stmtProps->execute();
    $resProps = $stmtProps->get_result();
    $nomes_props = [];
    while ($p = $resProps->fetch_assoc()) {
        $nomes_props[] = (string) $p['nome_razao'];
    }
    $stmtProps->close();

    $placeholders = implode(',', array_fill(0, count($propriedades), '?'));
    $sql = "
        SELECT
            a.id, a.tipo, a.data, a.status, a.observacoes, a.data_conclusao,
            a.quantidade, a.unidade,
            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad_area
            ON ad_area.apontamento_id = a.id AND ad_area.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad_area.valor
        LEFT JOIN apontamento_detalhes ad_prod
            ON ad_prod.apontamento_id = a.id AND ad_prod.campo = 'produto_id'
        LEFT JOIN produtos p ON p.id = ad_prod.valor
        LEFT JOIN propriedades prop ON prop.id = a.propriedade_id
        WHERE a.propriedade_id IN ($placeholders)
        AND COALESCE(a.data_conclusao, a.data) BETWEEN ? AND ?
    ";

    $params = $propriedades;
    $types = str_repeat('i', count($propriedades));
    $params[] = $data_ini;
    $params[] = $data_fim;
    $types .= 'ss';

    if ($cultivo !== '') {
        $sql .= ' AND p.nome = ?';
        $params[] = $cultivo;
        $types .= 's';
    }
    if ($area !== '') {
        $sql .= ' AND ar.nome = ?';
        $params[] = $area;
        $types .= 's';
    }
    if ($manejo !== '') {
        $sql .= ' AND a.tipo = ?';
        $params[] = $manejo;
        $types .= 's';
    }

    $sql .= ' ORDER BY a.data DESC';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $pendentes = [];
    $concluidos = [];
    $atrasados = [];
    $hoje = strtotime(date('Y-m-d'));

    while ($row = $res->fetch_assoc()) {
        $data_base = !empty($row['data_conclusao']) ? $row['data_conclusao'] : $row['data'];
        $data_item = strtotime((string) $data_base);

        if (strtolower((string) $row['status']) === 'concluido') {
            $concluidos[] = $row;
        } else {
            if ($data_item < $hoje) {
                $atrasados[] = $row;
            }
            $pendentes[] = $row;
        }
    }
    $stmt->close();

    $total_pendentes = count($pendentes);
    $total_concluidos = count($concluidos);
    $total_atrasados = count($atrasados);
    $total_geral = $total_pendentes + $total_concluidos;

    $pct_concluidos = $total_geral > 0 ? (int) round(($total_concluidos / $total_geral) * 100) : 0;
    $pct_pendentes = 100 - $pct_concluidos;
    $pct_atrasados = $total_pendentes > 0 ? (int) round(($total_atrasados / $total_pendentes) * 100) : 0;
    $pct_emdia = 100 - $pct_atrasados;

    return [
        'nomes_props' => $nomes_props,
        'concluidos' => $concluidos,
        'pendentes' => $pendentes,
        'atrasados' => $atrasados,
        'total_concluidos' => $total_concluidos,
        'total_pendentes' => $total_pendentes,
        'total_atrasados' => $total_atrasados,
        'total_geral' => $total_geral,
        'pct_concluidos' => $pct_concluidos,
        'pct_pendentes' => $pct_pendentes,
        'pct_atrasados' => $pct_atrasados,
        'pct_emdia' => $pct_emdia,
        'data_ini' => $data_ini,
        'data_fim' => $data_fim,
        'paginas_estimadas' => relatorioManejosEstimarPaginas($total_concluidos, $total_pendentes, $total_atrasados),
    ];
}
