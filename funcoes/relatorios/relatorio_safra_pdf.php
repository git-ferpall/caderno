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

    if (!$user_id) {
        throw new Exception("Usuário não autenticado.");
    }

    /* =====================================================
    FILTROS
    ===================================================== */

    $propriedades = $_POST['pfpropriedades'] ?? [];
    $cultivo = $_POST['pfcult'] ?? '';
    $area = $_POST['pfarea'] ?? '';
    $manejo = $_POST['pfmane'] ?? '';
    $data_ini = $_POST['pfini'] ?? date('Y-m-01');
    $data_fim = $_POST['pffin'] ?? date('Y-m-t');


    /* =====================================================
    BUSCA PROPRIEDADES
    ===================================================== */

    if (empty($propriedades)) {

        $stmtProp = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ?");
        $stmtProp->bind_param("i", $user_id);
        $stmtProp->execute();

        $resProp = $stmtProp->get_result();

        while ($row = $resProp->fetch_assoc()) {
            $propriedades[] = $row['id'];
        }

        $stmtProp->close();
    }

    if (empty($propriedades)) {
        throw new Exception("Nenhuma propriedade encontrada.");
    }

    $placeholders = implode(',', array_fill(0, count($propriedades), '?'));
    $types = str_repeat('i', count($propriedades));


    /* =====================================================
    NOMES PROPRIEDADES
    ===================================================== */

    $stmtProps = $mysqli->prepare("
        SELECT nome_razao 
        FROM propriedades 
        WHERE id IN ($placeholders)
    ");

    $stmtProps->bind_param($types, ...$propriedades);
    $stmtProps->execute();

    $resProps = $stmtProps->get_result();

    $nomes_props = [];

    while ($p = $resProps->fetch_assoc()) {
        $nomes_props[] = $p['nome_razao'];
    }

    $stmtProps->close();


    /* =====================================================
    QUERY PRINCIPAL
    ===================================================== */

    $sql = "

        SELECT 
            a.id,
            a.tipo,
            a.data,
            a.status,
            a.observacoes,
            a.data_conclusao,

            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome

        FROM apontamentos a

        LEFT JOIN apontamento_detalhes ad_area
            ON ad_area.apontamento_id = a.id
            AND ad_area.campo = 'area_id'

        LEFT JOIN areas ar
            ON ar.id = ad_area.valor

        LEFT JOIN apontamento_detalhes ad_prod
            ON ad_prod.apontamento_id = a.id
            AND ad_prod.campo = 'produto_id'

        LEFT JOIN produtos p
            ON p.id = ad_prod.valor

        LEFT JOIN propriedades prop
            ON prop.id = a.propriedade_id

        WHERE a.propriedade_id IN ($placeholders)
        AND COALESCE(a.data_conclusao,a.data) BETWEEN ? AND ?

    ";

    $params = [];
    $types = "";

    foreach ($propriedades as $pid) {
        $params[] = $pid;
        $types .= "i";
    }

    $params[] = $data_ini;
    $params[] = $data_fim;
    $types .= "ss";

    if (!empty($cultivo)) {
        $sql .= " AND p.nome = ?";
        $params[] = $cultivo;
        $types .= "s";
    }

    if (!empty($area)) {
        $sql .= " AND ar.nome = ?";
        $params[] = $area;
        $types .= "s";
    }

    if (!empty($manejo)) {
        $sql .= " AND a.tipo = ?";
        $params[] = $manejo;
        $types .= "s";
    }

    $sql .= " ORDER BY a.data DESC";


    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $res = $stmt->get_result();


    /* =====================================================
    ORGANIZA RESULTADOS
    ===================================================== */

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

    $pct_concluidos = $total_geral > 0
        ? round(($total_concluidos / $total_geral) * 100)
        : 0;

    $pct_pendentes = 100 - $pct_concluidos;

    $pct_atrasados = $total_pendentes > 0
        ? round(($total_atrasados / $total_pendentes) * 100)
        : 0;

    $pct_emdia = 100 - $pct_atrasados;


    /* =====================================================
    EVOLUÇÃO DE MANEJOS (por mês)
    ===================================================== */

    $grafico = [];

    foreach ($concluidos as $c) {

        $mes = date("Y-m", strtotime($c['data']));

        if (!isset($grafico[$mes])) {
            $grafico[$mes] = 0;
        }

        $grafico[$mes]++;
    }

    ksort($grafico);

    $mostrar_evolucao = count($grafico) > 1;


    /* =====================================================
    GRAFICO EVOLUÇÃO
    ===================================================== */

    $chartEvolucao = "";

    if ($mostrar_evolucao) {

        $chartConfig = [

            "type" => "line",

            "data" => [

                "labels" => array_keys($grafico),

                "datasets" => [[

                    "label" => "Manejos concluídos",

                    "data" => array_values($grafico),

                    "borderColor" => "#2e7d32",

                    "backgroundColor" => "rgba(46,125,50,0.15)",

                    "fill" => true

                ]]
            ],

            "options" => [
                "scales" => [
                    "y" => [
                        "beginAtZero" => true
                    ]
                ]
            ]
        ];

        $chartEvolucao = "https://quickchart.io/chart?c=" . urlencode(json_encode($chartConfig));
    }


    /* =====================================================
    PDF
    ===================================================== */

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 45,
        'margin_bottom' => 20,
        'tempDir' => __DIR__ . '/../../tmp/mpdf'
    ]);


    /* =====================================================
    HTML
    ===================================================== */

    $html = '

    <style>

    body { font-family: sans-serif; font-size:12px; }

    h1 { text-align:center; color:#2e7d32 }

    table{
        width:100%;
        border-collapse:collapse;
        margin-top:10px;
    }

    th,td{
        border:1px solid #ccc;
        padding:6px;
    }

    th{
        background:#4caf50;
        color:#fff;
    }

    .graficos{
        text-align:center;
        margin:20px 0;
    }

    </style>

    ';

    $html .= "<h1>Relatório de Manejos</h1>";

    $html .= "
    <b>Propriedades:</b> ".implode(', ', $nomes_props)."<br>
    <b>Período:</b> ".date('d/m/Y',strtotime($data_ini))." até ".date('d/m/Y',strtotime($data_fim))."<br>
    <b>Total:</b> $total_geral
    ";


    /* =====================================================
    GRAFICOS PIZZA
    ===================================================== */

    function graficoPizza($titulo,$labels,$data,$cores){

        $chart=[

            "type"=>"pie",

            "data"=>[
                "labels"=>$labels,
                "datasets"=>[[

                    "data"=>$data,
                    "backgroundColor"=>$cores

                ]]
            ],

            "options"=>[
                "plugins"=>[
                    "title"=>[
                        "display"=>true,
                        "text"=>$titulo
                    ]
                ]
            ]

        ];

        return '<img style="width:45%" src="https://quickchart.io/chart?c='.urlencode(json_encode($chart)).'">';
    }

    $html.='<div class="graficos">';

    $html.=graficoPizza(
        "Concluídos x Pendentes",
        ["Concluídos","Pendentes"],
        [$pct_concluidos,$pct_pendentes],
        ["#4caf50","#ff9800"]
    );

    $html.=graficoPizza(
        "Em dia x Atrasados",
        ["Em dia","Atrasados"],
        [$pct_emdia,$pct_atrasados],
        ["#4caf50","#e53935"]
    );

    $html.='</div>';


    /* =====================================================
    GRAFICO EVOLUÇÃO
    ===================================================== */

    if($mostrar_evolucao){

        $html.="

        <h2>Evolução de Manejos</h2>

        <img src='$chartEvolucao' style='width:100%'>

        ";

    }


    /* =====================================================
    TABELA
    ===================================================== */

    $html.='

    <table>

    <tr>
    <th>Data</th>
    <th>Propriedade</th>
    <th>Área</th>
    <th>Produto</th>
    <th>Tipo</th>
    <th>Status</th>
    </tr>

    ';

    foreach($concluidos as $d){

        $html.='

        <tr>

        <td>'.date('d/m/Y',strtotime($d['data'])).'</td>
        <td>'.$d['propriedade_nome'].'</td>
        <td>'.$d['area_nome'].'</td>
        <td>'.$d['produto_nome'].'</td>
        <td>'.$d['tipo'].'</td>
        <td>'.$d['status'].'</td>

        </tr>

        ';
    }

    $html.='</table>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('relatorio.pdf',Destination::INLINE);

}

catch(Exception $e){

    echo "<pre>Erro: ".$e->getMessage()."</pre>";

}