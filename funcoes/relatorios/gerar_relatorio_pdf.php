<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $propriedades = $_POST['propriedades'] ?? [];
    $cultivo = $_POST['cultivo'] ?? '';
    $area = $_POST['area'] ?? '';
    $manejo = $_POST['manejo'] ?? '';
    $data_ini = $_POST['data_ini'] ?? date('Y-m-01');
    $data_fim = $_POST['data_fim'] ?? date('Y-m-t');

    // === Propriedades padrão ===
    if (empty($propriedades)) {
        $stmtProp = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ?");
        $stmtProp->bind_param("i", $user_id);
        $stmtProp->execute();
        $resProp = $stmtProp->get_result();
        while ($row = $resProp->fetch_assoc()) $propriedades[] = $row['id'];
        $stmtProp->close();
    }
    if (empty($propriedades)) throw new Exception("Nenhuma propriedade encontrada para este usuário.");

    $placeholders = implode(',', array_fill(0, count($propriedades), '?'));

    // === Consulta ===
    $sql = "
        SELECT 
            a.id, a.tipo, a.data, a.status, a.observacoes,
            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad_area ON ad_area.apontamento_id = a.id AND ad_area.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad_area.valor
        LEFT JOIN apontamento_detalhes ad_prod ON ad_prod.apontamento_id = a.id AND ad_prod.campo = 'produto_id'
        LEFT JOIN produtos p ON p.id = ad_prod.valor
        LEFT JOIN propriedades prop ON prop.id = a.propriedade_id
        WHERE a.data BETWEEN ? AND ?
          AND a.propriedade_id IN ($placeholders)
        ORDER BY a.data DESC
    ";

    $params = [$data_ini, $data_fim];
    $types = "ss";
    foreach ($propriedades as $pid) { $params[] = $pid; $types .= "i"; }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $pendentes = [];
    $concluidos = [];
    $atrasados = [];
    $hoje = strtotime(date('Y-m-d'));

    while ($row = $res->fetch_assoc()) {
        $data_item = strtotime($row['data']);
        if ($row['status'] == 'concluido') {
            $concluidos[] = $row;
        } else {
            if ($data_item < $hoje) $atrasados[] = $row;
            $pendentes[] = $row;
        }
    }

    // === Estatísticas ===
    $total_pendentes = count($pendentes);
    $total_concluidos = count($concluidos);
    $total_atrasados = count($atrasados);
    $total_geral = $total_pendentes + $total_concluidos;

    $pct_concluidos = $total_geral > 0 ? round(($total_concluidos / $total_geral) * 100) : 0;
    $pct_pendentes  = 100 - $pct_concluidos;
    $pct_atrasados  = $total_pendentes > 0 ? round(($total_atrasados / $total_pendentes) * 100) : 0;
    $pct_emdia      = 100 - $pct_atrasados;

    // === Cria PDF ===
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 40,
        'margin_bottom' => 20,
        'tempDir' => __DIR__ . '/../../tmp/mpdf'
    ]);

    $logo_path = __DIR__ . '/../../img/logo.png';
    $logo_base64 = file_exists($logo_path) ? base64_encode(file_get_contents($logo_path)) : null;
    $logo_html = $logo_base64 ? '<img src="data:image/png;base64,' . $logo_base64 . '" width="120">' : '<strong>Frutag</strong>';

    $mpdf->SetHTMLHeader('
        <div style="text-align:left; border-bottom:1px solid #ddd; padding-bottom:6px;">
            ' . $logo_html . '
            <span style="float:right; font-size:12px; margin-top:10px; color:#555;">Relatório de Manejos</span>
        </div>
    ');

    $mpdf->SetHTMLFooter('
        <div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
            Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
        </div>
    ');

    // === Estilos ===
    $html = '
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #2e7d32; margin-bottom: 5px; }
        h2 { text-align: left; color: #555; border-bottom:1px solid #ccc; margin-top:20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #4caf50; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .atrasado { background-color: #ffebee; color: #c62828; font-weight: bold; }
        .resumo { margin:10px 0 20px 0; font-size:13px; }
        .graficos { text-align:center; margin:20px 0; }
        .grafico-img { width:45%; display:inline-block; margin:0 2%; }
    </style>
    ';

    $html .= '<h1>Relatório de Manejos</h1>';
    $html .= '<div class="resumo">
        <strong>Período:</strong> ' . date('d/m/Y', strtotime($data_ini)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '<br>
        <strong>Total:</strong> ' . $total_geral . ' manejos |
        <strong>Concluídos:</strong> ' . $total_concluidos . ' |
        <strong>Pendentes:</strong> ' . $total_pendentes . ' |
        <strong>Atrasados:</strong> ' . $total_atrasados . '
    </div>';

    // === Gráficos (Chart.js em imagem Base64) ===
    function gerarGrafico($labels, $data, $cores) {
        $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'pie',
            'data' => ['labels' => $labels, 'datasets' => [['data' => $data, 'backgroundColor' => $cores]]],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]]
        ]));
        return '<img class="grafico-img" src="' . $chartUrl . '">';
    }

    $html .= '<div class="graficos">
        ' . gerarGrafico(['Concluídos', 'Pendentes'], [$pct_concluidos, $pct_pendentes], ['#4caf50','#ff9800']) . '
        ' . gerarGrafico(['Em dia', 'Atrasados'], [$pct_emdia, $pct_atrasados], ['#4caf50','#e53935']) . '
    </div>';

    // === Tabelas ===
    function montarTabela($titulo, $dados, $classe = '') {
        if (empty($dados)) return '';
        $html = '<h2>' . $titulo . '</h2><table><thead>
                    <tr><th>Data</th><th>Propriedade</th><th>Área</th><th>Produto</th><th>Tipo</th><th>Status</th><th>Observações</th></tr>
                </thead><tbody>';
        foreach ($dados as $d) {
            $extraClass = ($classe && strtotime($d['data']) < strtotime(date('Y-m-d')) && $d['status'] != 'concluido') ? ' class="' . $classe . '"' : '';
            $html .= '<tr' . $extraClass . '>
                        <td>' . date('d/m/Y', strtotime($d['data'])) . '</td>
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
    if (!empty($atrasados)) {
        $html .= montarTabela("⚠ Pendências Atrasadas", $atrasados, 'atrasado');
    }

    $mpdf->WriteHTML($html);
    $mpdf->Output('relatorio.pdf', Destination::INLINE);

} catch (Exception $e) {
    echo "<pre>Erro: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
