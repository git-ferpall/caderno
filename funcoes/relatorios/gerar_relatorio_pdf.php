<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

session_start();

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado.");

    // === Filtros ===
    $propriedades = $_POST['pfpropriedades'] ?? [];
    $cultivo = $_POST['pfcult'] ?? '';
    $area = $_POST['pfarea'] ?? '';
    $manejo = $_POST['pfmane'] ?? '';
    $data_ini = $_POST['pfini'] ?? date('Y-m-01');
    $data_fim = $_POST['pffin'] ?? date('Y-m-t');

    // === Pega todas as propriedades se nada foi selecionado ===
    if (empty($propriedades)) {
        $stmtProp = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ?");
        $stmtProp->bind_param("i", $user_id);
        $stmtProp->execute();
        $resProp = $stmtProp->get_result();
        while ($row = $resProp->fetch_assoc()) $propriedades[] = $row['id'];
        $stmtProp->close();
    }

    if (empty($propriedades)) throw new Exception("Nenhuma propriedade encontrada para este usuário.");

    // === Lista os nomes das propriedades selecionadas ===
    $placeholdersProps = implode(',', array_fill(0, count($propriedades), '?'));
    $typesProps = str_repeat('i', count($propriedades));

    $stmtProps = $mysqli->prepare("SELECT nome_razao FROM propriedades WHERE id IN ($placeholdersProps)");
    $stmtProps->bind_param($typesProps, ...$propriedades);
    $stmtProps->execute();
    $resProps = $stmtProps->get_result();
    $nomes_props = [];
    while ($p = $resProps->fetch_assoc()) $nomes_props[] = $p['nome_razao'];
    $stmtProps->close();

    // === Query principal ===
    $placeholders = implode(',', array_fill(0, count($propriedades), '?'));

    $sql = "
    SELECT 
            a.id, a.tipo, a.data, a.status, a.observacoes, a.data_conclusao,
            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad_area 
            ON ad_area.apontamento_id = a.id 
            AND ad_area.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad_area.valor
        LEFT JOIN apontamento_detalhes ad_prod 
            ON ad_prod.apontamento_id = a.id 
            AND ad_prod.campo = 'produto_id'
        LEFT JOIN produtos p ON p.id = ad_prod.valor
        LEFT JOIN propriedades prop ON prop.id = a.propriedade_id
        WHERE a.propriedade_id IN ($placeholders)
        AND COALESCE(a.data_conclusao, a.data) BETWEEN ? AND ?
    ";

    $params = [];
    $types  = "";

    // 🔹 Primeiro vêm os IDs (porque estão primeiro na query)
    foreach ($propriedades as $pid) {
        $params[] = $pid;
        $types   .= "i";
    }

    // 🔹 Depois vêm as datas
    $params[] = $data_ini;
    $params[] = $data_fim;
    $types   .= "ss";

    // 🔹 FILTRO CULTIVO
    if (!empty($cultivo)) {
        $sql .= " AND p.nome = ?";
        $params[] = $cultivo;
        $types   .= "s";
    }

    // 🔹 FILTRO ÁREA
    if (!empty($area)) {
        $sql .= " AND ar.nome = ?";
        $params[] = $area;
        $types   .= "s";
    }

    // 🔹 FILTRO MANEJO
    if (!empty($manejo)) {
        $sql .= " AND a.tipo = ?";
        $params[] = $manejo;
        $types   .= "s";
    }

    $sql .= " ORDER BY a.data DESC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $pendentes = [];
    $concluidos = [];
    $atrasados = [];
    $hoje = strtotime(date('Y-m-d'));

    while ($row = $res->fetch_assoc()) {

        $data_base = !empty($row['data_conclusao']) 
            ? $row['data_conclusao'] 
            : $row['data'];

        $data_item = strtotime($data_base);

        if (strtolower($row['status']) === 'concluido') {
            $concluidos[] = $row;
        } else {
            if ($data_item < $hoje) {
                $atrasados[] = $row;
            }
            $pendentes[] = $row;
        }
    }

    $total_pendentes = count($pendentes);
    $total_concluidos = count($concluidos);
    $total_atrasados = count($atrasados);
    $total_geral = $total_pendentes + $total_concluidos;

    $pct_concluidos = $total_geral > 0 ? round(($total_concluidos / $total_geral) * 100) : 0;
    $pct_pendentes  = 100 - $pct_concluidos;
    $pct_atrasados  = $total_pendentes > 0 ? round(($total_atrasados / $total_pendentes) * 100) : 0;
    $pct_emdia      = 100 - $pct_atrasados;

    // === PDF ===
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 45,
        'margin_bottom' => 20,
        'tempDir' => __DIR__ . '/../../tmp/mpdf'
    ]);

    $logo_frutag = __DIR__ . '/../../img/logo-frutag.png';
    $logo_caderno = __DIR__ . '/../../img/logo-color.png';

    $img_frutag = file_exists($logo_frutag) ? base64_encode(file_get_contents($logo_frutag)) : '';
    $img_caderno = file_exists($logo_caderno) ? base64_encode(file_get_contents($logo_caderno)) : '';

    $mpdf->SetHTMLHeader('
        <div style="border-bottom:1px solid #ccc; padding-bottom:5px;">
            <img src="data:image/png;base64,' . $img_frutag . '" width="120" style="float:left;">
            <img src="data:image/png;base64,' . $img_caderno . '" width="120" style="float:right;">
            <div style="text-align:center; font-weight:bold; font-size:16px; color:#2e7d32;">Relatório de Manejos - Caderno de Campo</div>
        </div>
    ');

    $mpdf->SetHTMLFooter('
        <div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
            Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
        </div>
    ');

    // === Estilo + gráficos ===
    $html = '
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #2e7d32; margin-bottom: 5px; }
        h2 { text-align: left; color: #555; border-bottom:1px solid #ccc; margin-top:25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #4caf50; color: white; }
        .atrasado { background-color: #ffebee; color: #c62828; font-weight:bold; }
        .resumo { margin:10px 0 20px 0; font-size:13px; }
        .graficos { text-align:center; margin:20px 0; }
        .grafico-img { width:45%; display:inline-block; margin:0 2%; }
    </style>
    ';

    $html .= '<h1>Relatório de Manejos</h1>';
    $html .= '<div class="resumo">
        <strong>Propriedades:</strong> ' . implode(', ', $nomes_props) . '<br>
        <strong>Período:</strong> ' . date('d/m/Y', strtotime($data_ini)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '<br>
        <strong>Total:</strong> ' . $total_geral . ' | 
        <strong>Concluídos:</strong> ' . $total_concluidos . ' | 
        <strong>Pendentes:</strong> ' . $total_pendentes . ' | 
        <strong>Atrasados:</strong> ' . $total_atrasados . '
    </div>';

    // === gráficos pizza com destaque nos valores ===
    function gerarGrafico($titulo, $labels, $data, $cores) {
        $chart = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $cores
                ]]
            ],
            'options' => [
                'plugins' => [
                    'title' => ['display' => true, 'text' => $titulo, 'font' => ['size' => 15]],
                    'legend' => ['position' => 'bottom'],
                    'datalabels' => [
                        'color' => '#fff',
                        'font' => ['weight' => 'bold', 'size' => 16],
                        'formatter' => "(value) => value + '%'"
                    ]
                ]
            ]
        ];
        return '<img class="grafico-img" src="https://quickchart.io/chart?c=' . urlencode(json_encode($chart)) . '">';
    }

    $html .= '<div class="graficos">'
        . gerarGrafico('Concluídos x Pendentes', ['Concluídos', 'Pendentes'], [$pct_concluidos, $pct_pendentes], ['#4caf50','#ff9800'])
        . gerarGrafico('Em dia x Atrasados', ['Em dia', 'Atrasados'], [$pct_emdia, $pct_atrasados], ['#4caf50','#e53935'])
        . '</div>';

    // === Monta as tabelas ===
    function montarTabela($titulo, $dados, $classe = '') {
        if (empty($dados)) return '';

        $html = '<h2>' . $titulo . '</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Propriedade</th>
                    <th>Área</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($dados as $d) {

            // 🔹 Define qual data mostrar
            $dataExibida = (
                strtolower($d['status']) === 'concluido'
                && !empty($d['data_conclusao'])
            )
            ? $d['data_conclusao']
            : $d['data'];

            // 🔹 Define classe de atraso (apenas para não concluídos)
            $extra = (
                $classe 
                && strtotime($d['data']) < strtotime(date('Y-m-d')) 
                && $d['status'] !== 'concluido'
            )
            ? ' class="' . $classe . '"'
            : '';

            $html .= '<tr' . $extra . '>
                <td>' . date('d/m/Y', strtotime($dataExibida)) . '</td>
                <td>' . htmlspecialchars($d['propriedade_nome'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['area_nome'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['produto_nome'] ?? '—') . '</td>
                <td>' . ucfirst($d['tipo'] ?? '—') . '</td>
                <td>' . ucfirst($d['status'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['observacoes'] ?? '—') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    $html .= montarTabela("Manejos Concluídos", $concluidos);
    $html .= montarTabela("Manejos Pendentes", $pendentes);
    if (!empty($atrasados)) $html .= montarTabela("⚠ Pendências Atrasadas", $atrasados, 'atrasado');

    $mpdf->WriteHTML($html);
    $mpdf->Output('relatorio.pdf', Destination::INLINE);

} catch (Exception $e) {
    echo "<pre>Erro: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
