<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/mpdf_bootstrap.php';

use Mpdf\Mpdf;

session_start();

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int) ($payload['sub'] ?? 0);
}
if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}

/* ===============================
📥 PARAMETROS
=============================== */

$data_ini = $_POST['data_ini'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;
$area_filtro = (int) ($_POST['area'] ?? 0);
$props = $_POST['propriedade'] ?? [];

if (!is_array($props)) {
    $props = [$props];
}
$props = array_values(array_filter(array_map('intval', $props)));

if (!$data_ini || !$data_fim || !$props) {
    http_response_code(400);
    die('Parâmetros inválidos');
}

/* ===============================
🔐 VALIDAR PROPRIEDADES DO USUÁRIO
=============================== */

$placeholders = implode(',', array_fill(0, count($props), '?'));
$types = str_repeat('i', count($props)) . 'i';
$params = array_merge($props, [$user_id]);

$stmtAuth = $mysqli->prepare("
    SELECT id, nome_razao
    FROM propriedades
    WHERE id IN ($placeholders) AND user_id = ?
");
$stmtAuth->bind_param($types, ...$params);
$stmtAuth->execute();
$resAuth = $stmtAuth->get_result();

$props_validas = [];
while ($row = $resAuth->fetch_assoc()) {
    $props_validas[(int) $row['id']] = (string) $row['nome_razao'];
}
$stmtAuth->close();

if (!$props_validas) {
    http_response_code(403);
    die('Propriedade não encontrada');
}

$prop_ids = array_keys($props_validas);

/* ===============================
📌 TIPOS
=============================== */

$tipos = [
    'inseticida',
    'herbicida',
    'fertilizante',
    'fungicida',
    'adubacao_organica',
    'adubacao_calcario',
];

$tipos_sql = "'" . implode("','", $tipos) . "'";

/* ===============================
📊 QUERY
=============================== */

$placeholdersProps = implode(',', array_fill(0, count($prop_ids), '?'));
$sql = "
    SELECT
        a.id,
        a.tipo,
        a.data,
        a.status,
        a.quantidade,
        a.unidade,
        a.observacoes,
        a.data_conclusao,
        a.propriedade_id,
        pr.nome_razao AS propriedade_nome,
        ar.id AS area_id,
        ar.nome AS area_nome,
        MAX(CASE WHEN ad.campo IN ('herbicida','fungicida','inseticida','fertilizante') THEN ad.valor END) AS produto_nome,
        MAX(CASE WHEN ad.campo IN ('produto_id','produto') THEN ad.valor END) AS produto_ref,
        MAX(CASE WHEN ad.campo = 'carencia_dias' THEN ad.valor END) AS carencia_dias,
        MAX(CASE WHEN ad.campo = 'ingrediente_ativo' THEN ad.valor END) AS ingrediente_ativo,
        MAX(CASE WHEN ad.campo = 'data_liberacao_colheita' THEN ad.valor END) AS data_liberacao
    FROM apontamentos a
    INNER JOIN apontamento_detalhes ada
        ON ada.apontamento_id = a.id AND ada.campo = 'area_id'
    INNER JOIN areas ar ON ar.id = CAST(ada.valor AS UNSIGNED)
    INNER JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id
    LEFT JOIN propriedades pr ON pr.id = a.propriedade_id
    WHERE a.tipo IN ($tipos_sql)
      AND a.propriedade_id IN ($placeholdersProps)
      AND COALESCE(a.data_conclusao, a.data) BETWEEN ? AND ?
";

$paramsQuery = array_merge($prop_ids, [$data_ini, $data_fim]);
$typesQuery = str_repeat('i', count($prop_ids)) . 'ss';

if ($area_filtro > 0) {
    $sql .= ' AND ada.valor = ?';
    $paramsQuery[] = (string) $area_filtro;
    $typesQuery .= 's';
}

$sql .= '
    GROUP BY
        a.id, a.tipo, a.data, a.status, a.quantidade, a.unidade,
        a.observacoes, a.data_conclusao, a.propriedade_id,
        pr.nome_razao, ar.id, ar.nome
    ORDER BY pr.nome_razao, ar.nome, a.data DESC, a.id DESC
';

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($typesQuery, ...$paramsQuery);
$stmt->execute();
$res = $stmt->get_result();

if (!$res) {
    http_response_code(500);
    die('Erro ao buscar aplicações');
}

/* ===============================
🔄 HELPERS
=============================== */

function converterUnidade(float $quantidade, ?string $unidade): array
{
    $u = strtolower(trim((string) $unidade));

    return match ($u) {
        'ml' => [$quantidade / 1000, 'L'],
        'l' => [$quantidade, 'L'],
        'ton', 't' => [$quantidade * 1000, 'kg'],
        'kg' => [$quantidade, 'kg'],
        default => [$quantidade, $unidade ?: ''],
    };
}

function nomeTipo(string $tipo): string
{
    $mapa = [
        'inseticida' => 'Inseticida',
        'herbicida' => 'Herbicida',
        'fertilizante' => 'Fertilizante',
        'fungicida' => 'Fungicida',
        'adubacao_organica' => 'Adubação Orgânica',
        'adubacao_calcario' => 'Adubação Calcário',
    ];

    return $mapa[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

function htmlEsc(?string $valor, string $fallback = '—'): string
{
    if ($valor === null || trim($valor) === '') {
        return htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/** @var array<int, string> */
$cacheProdutos = [];

function resolverProdutoNome(mysqli $mysqli, array &$cacheProdutos, array $row): string
{
    $nome = trim((string) ($row['produto_nome'] ?? ''));
    if ($nome !== '') {
        return $nome;
    }

    $ref = trim((string) ($row['produto_ref'] ?? ''));
    if ($ref === '') {
        return '—';
    }

    if (!ctype_digit($ref)) {
        return $ref;
    }

    $id = (int) $ref;
    if (isset($cacheProdutos[$id])) {
        return $cacheProdutos[$id];
    }

    $stmt = $mysqli->prepare('SELECT nome FROM produtos WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $cacheProdutos[$id] = trim((string) ($prod['nome'] ?? $ref));

    return $cacheProdutos[$id] !== '' ? $cacheProdutos[$id] : '—';
}

/* ===============================
📊 AGRUPAMENTO
=============================== */

$dados = [];
$total_geral = 0;

while ($row = $res->fetch_assoc()) {
    $row['produto'] = resolverProdutoNome($mysqli, $cacheProdutos, $row);

    $prop = $row['propriedade_nome'] ?? 'Sem propriedade';
    $area = $row['area_nome'] ?? 'Não informada';

    [$qtd, $unidade] = converterUnidade((float) $row['quantidade'], $row['unidade']);

    if (!isset($dados[$prop])) {
        $dados[$prop] = [];
    }

    if (!isset($dados[$prop][$area])) {
        $dados[$prop][$area] = [
            'pendentes' => [],
            'concluidos' => [],
            'totais' => [],
            'por_tipo' => [],
            'total_registros' => 0,
            'total_concluidos' => 0,
        ];
    }

    if (!isset($dados[$prop][$area]['totais'][$unidade])) {
        $dados[$prop][$area]['totais'][$unidade] = 0;
    }

    $tipo = (string) ($row['tipo'] ?? '');
    if (!isset($dados[$prop][$area]['por_tipo'][$tipo])) {
        $dados[$prop][$area]['por_tipo'][$tipo] = 0;
    }
    $dados[$prop][$area]['por_tipo'][$tipo]++;
    $dados[$prop][$area]['total_registros']++;
    $total_geral++;

    if (($row['status'] ?? '') === 'concluido') {
        $dados[$prop][$area]['concluidos'][] = $row;
        $dados[$prop][$area]['total_concluidos']++;
        $dados[$prop][$area]['totais'][$unidade] += $qtd;
    } else {
        $dados[$prop][$area]['pendentes'][] = $row;
    }
}

$stmt->close();

/* ===============================
📊 TABELA
=============================== */

function tabelaAplicacoes(array $registros): string
{
    if (!$registros) {
        return '<p style="color:#666;font-size:11px;">Nenhum registro</p>';
    }

    $html = "<table border='1' width='100%' cellspacing='0' cellpadding='5' style='font-size:10px;border-collapse:collapse;'>
    <thead style='background:#eee;'>
    <tr>
        <th>Data</th>
        <th>Tipo</th>
        <th>Produto</th>
        <th>Quantidade</th>
        <th>Carência</th>
        <th>Status</th>
        <th>Conclusão</th>
        <th>Obs.</th>
    </tr>
    </thead><tbody>";

    foreach ($registros as $d) {
        $status = strtolower((string) ($d['status'] ?? ''));
        $cor = $status === 'concluido' ? '#2e7d32' : '#c62828';
        $bg = $status === 'concluido' ? '#e8f5e9' : '#ffebee';

        $data_conclusao = !empty($d['data_conclusao'])
            ? date('d/m/Y', strtotime((string) $d['data_conclusao']))
            : '—';

        $carencia = !empty($d['carencia_dias'])
            ? htmlEsc((string) $d['carencia_dias']) . ' d'
            : '—';

        if (!empty($d['data_liberacao'])) {
            $carencia .= '<br><small>Lib.: ' . date('d/m/Y', strtotime((string) $d['data_liberacao'])) . '</small>';
        }

        $obs = trim((string) ($d['observacoes'] ?? ''));
        if ($obs === '' && !empty($d['ingrediente_ativo'])) {
            $obs = 'I.A.: ' . trim((string) $d['ingrediente_ativo']);
        }

        $html .= "<tr style='background:$bg'>
            <td>" . date('d/m/Y', strtotime((string) $d['data'])) . "</td>
            <td>" . htmlEsc(nomeTipo((string) $d['tipo'])) . "</td>
            <td><b>" . htmlEsc((string) ($d['produto'] ?? '—')) . "</b></td>
            <td>" . htmlEsc((string) $d['quantidade']) . ' ' . htmlEsc((string) $d['unidade']) . "</td>
            <td>$carencia</td>
            <td style='color:$cor;font-weight:bold;'>" . htmlEsc(ucwords($status)) . "</td>
            <td>$data_conclusao</td>
            <td>" . htmlEsc($obs !== '' ? $obs : '—') . "</td>
        </tr>";
    }

    $html .= '</tbody></table><br>';

    return $html;
}

function resumoPorTipo(array $porTipo): string
{
    if (!$porTipo) {
        return '';
    }

    ksort($porTipo);

    $html = '<p style="margin:8px 0;"><b>Resumo por tipo:</b> ';
    $partes = [];
    foreach ($porTipo as $tipo => $qtd) {
        $partes[] = htmlEsc(nomeTipo((string) $tipo)) . " ($qtd)";
    }
    $html .= implode(' · ', $partes) . '</p>';

    return $html;
}

/* ===============================
📄 HTML
=============================== */

$html = '
<h1>Relatório Fitossanitário — Aplicações</h1>
<p><b>Período:</b> ' . date('d/m/Y', strtotime($data_ini)) . ' até ' . date('d/m/Y', strtotime($data_fim)) . '</p>
<p style="color:#555;font-size:11px;">Fungicidas, herbicidas, inseticidas, fertilizantes e adubações registrados no período.</p>
';

if ($total_geral === 0) {
    $html .= '
    <div style="border:1px solid #f9a825;background:#fff8e1;padding:16px;border-radius:8px;margin-top:20px;">
        <b>Nenhuma aplicação encontrada</b> para os filtros selecionados.
        Verifique propriedade, área e intervalo de datas.
    </div>';
}

foreach ($dados as $prop => $areasGrupo) {
    $html .= "<h2 style='margin-top:20px;color:#2e7d32;'>Propriedade: " . htmlEsc($prop) . '</h2>';

    foreach ($areasGrupo as $area => $d) {
        $total = $d['total_registros'];
        $ok = $d['total_concluidos'];
        $ef = $total > 0 ? ($ok / $total) * 100 : 0;
        $ef_format = number_format($ef, 1, ',', '.');
        $cor = $ef >= 80 ? '#2e7d32' : ($ef >= 50 ? '#f9a825' : '#c62828');

        $html .= "
        <div style='border:1px solid #ddd;border-radius:8px;padding:12px;margin-bottom:20px;'>
            <div style='background:#2e7d32;color:white;padding:10px;border-radius:5px;'>
                Área: <b>" . htmlEsc($area) . "</b>
            </div>

            <p style='margin-top:10px;'>
                <b>Total de aplicações:</b> $total &nbsp;|&nbsp;
                <b>Concluídas:</b> $ok &nbsp;|&nbsp;
                <b>Eficiência:</b>
                <span style='color:$cor;font-weight:bold;'>$ef_format%</span>
            </p>

            " . resumoPorTipo($d['por_tipo']) . "
            <p><b>Total aplicado (concluídos):</b><br>";

        $temTotal = false;
        foreach ($d['totais'] as $un => $valor) {
            if ($valor <= 0) {
                continue;
            }
            $temTotal = true;
            $valor_formatado = $un === 'L'
                ? number_format($valor, 3, ',', '.')
                : number_format($valor, 2, ',', '.');
            $html .= "<span style='color:#2e7d32;font-weight:bold;'>$valor_formatado $un</span><br>";
        }
        if (!$temTotal) {
            $html .= '<span style="color:#666;">—</span>';
        }

        $html .= '</p>';
        $html .= '<h3 style="font-size:12px;color:#444;">Aplicações concluídas</h3>';
        $html .= tabelaAplicacoes($d['concluidos']);
        $html .= '<h3 style="font-size:12px;color:#444;">Aplicações pendentes</h3>';
        $html .= tabelaAplicacoes($d['pendentes']);
        $html .= '</div>';
    }
}

/* ===============================
📄 MPDF
=============================== */

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 45,
    'margin_bottom' => 20,
    'tempDir' => cadernoMpdfTempDir(),
]);

$logo_frutag = __DIR__ . '/../../img/logo-frutag.png';
$logo_caderno = __DIR__ . '/../../img/logo-color.png';

$img_frutag = file_exists($logo_frutag) ? base64_encode(file_get_contents($logo_frutag)) : '';
$img_caderno = file_exists($logo_caderno) ? base64_encode(file_get_contents($logo_caderno)) : '';

$mpdf->SetHTMLHeader('
<div style="border-bottom:1px solid #ccc;padding-bottom:5px;font-family:sans-serif;">
    <div style="width:33%;float:left;">
        <img src="data:image/png;base64,' . $img_frutag . '" width="110">
    </div>
    <div style="width:34%;float:left;text-align:center;font-weight:bold;font-size:16px;color:#2e7d32;">
        Relatório Fitossanitário<br>
        <span style="font-size:12px;color:#666;">
            Período: ' . date('d/m/Y', strtotime($data_ini)) . ' até ' . date('d/m/Y', strtotime($data_fim)) . '
        </span>
    </div>
    <div style="width:33%;float:right;text-align:right;">
        <img src="data:image/png;base64,' . $img_caderno . '" width="110">
    </div>
    <div style="clear:both;"></div>
</div>
');

$mpdf->SetHTMLFooter('
<div style="border-top:1px solid #ccc;text-align:center;font-size:10px;color:#777;padding-top:4px;">
    Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
</div>
');

$mpdf->WriteHTML($html);

header('Content-Type: application/pdf');
$mpdf->Output('relatorio_fitossanitario.pdf', 'I');
