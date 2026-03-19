<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../configuracao/configuracao_conexao.php';
require_once __DIR__.'/../../vendor/autoload.php';

use Mpdf\Mpdf;

/* ===============================
📥 PARAMETROS
=============================== */

$data_ini = $_POST['data_ini'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;
$areas    = $_POST['area'] ?? null;
$props    = $_POST['propriedade'] ?? [];

if (!is_array($props)) $props = [$props];

if (!$data_ini || !$data_fim || empty($props)) {
    die("Parâmetros inválidos");
}

/* ===============================
📌 TIPOS
=============================== */

$tipos = [
    'inseticida',
    'herbicida',
    'fertilizante',
    'fungicida',
    'adubacao_organica',
    'adubacao_calcario'
];

$in_props = implode(",", array_map('intval', $props));

/* ===============================
📌 FILTRO AREA
=============================== */

$filtro_area = "";
if (!empty($areas)) {
    $filtro_area = "AND EXISTS (
        SELECT 1 FROM apontamento_detalhes ad
        WHERE ad.apontamento_id = ap.id
        AND ad.campo = 'area_id'
        AND ad.valor = '".intval($areas)."'
    )";
}

/* ===============================
📊 QUERY (COM JOIN OTIMIZADO)
=============================== */

$sql = "
SELECT 
    ap.id,
    ap.tipo,
    ap.data,
    ap.quantidade,
    ap.unidade,
    ap.status,
    ap.data_conclusao,
    ar.nome as area_nome

FROM apontamentos ap

LEFT JOIN apontamento_detalhes ad 
    ON ad.apontamento_id = ap.id 
    AND ad.campo = 'area_id'

LEFT JOIN areas ar 
    ON ar.id = ad.valor

WHERE ap.tipo IN ('".implode("','",$tipos)."')
AND ap.propriedade_id IN ($in_props)
AND ap.data BETWEEN '$data_ini' AND '$data_fim'
$filtro_area
ORDER BY ar.nome, ap.data DESC
";

$res = $mysqli->query($sql);

if(!$res){
    die("Erro SQL: ".$mysqli->error);
}

/* ===============================
📊 AGRUPAMENTO (AREA + UNIDADE)
=============================== */

function converterUnidade($quantidade, $unidade){

    $u = strtolower(trim($unidade));

    switch($u){

        // líquidos → base L
        case 'ml':
            return [$quantidade / 1000, 'L'];

        case 'l':
        case 'litro':
        case 'litros':
            return [$quantidade, 'L'];

        // peso → base kg
        case 'ton':
        case 't':
            return [$quantidade * 1000, 'kg'];

        case 'kg':
            return [$quantidade, 'kg'];

        default:
            return [$quantidade, $unidade];
    }
}

$dados_por_area = [];

while($row = $res->fetch_assoc()){

    $area_nome = $row['area_nome'] ?? 'Não informada';

    list($qtd_convertida, $unidade_base) = converterUnidade(
        floatval($row['quantidade']),
        $row['unidade']
    );

    if(!isset($dados_por_area[$area_nome])){
        $dados_por_area[$area_nome] = [
            'pendentes' => [],
            'concluidos' => [],
            'totais' => []
        ];
    }

    if(!isset($dados_por_area[$area_nome]['totais'][$unidade_base])){
        $dados_por_area[$area_nome]['totais'][$unidade_base] = 0;
    }

    if($row['status'] == 'concluido'){
        $dados_por_area[$area_nome]['concluidos'][] = $row;

        // soma já convertido
        $dados_por_area[$area_nome]['totais'][$unidade_base] += $qtd_convertida;

    }else{
        $dados_por_area[$area_nome]['pendentes'][] = $row;
    }
}
/* ===============================
📊 TABELA
=============================== */

function tabela($dados, $titulo){

    $html = "<h3>$titulo</h3>";

    if(empty($dados)){
        return $html."<p>Nenhum registro</p>";
    }

    $html .= "<table border='1' width='100%' cellspacing='0' cellpadding='6'>
        <thead style='background:#eee;'>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Status</th>
                <th>Conclusão</th>
            </tr>
        </thead>
        <tbody>";

    foreach($dados as $d){

        $cor = ($d['status'] == 'concluido') ? "#2e7d32" : "#c62828";
        $bg  = ($d['status'] == 'concluido') ? "#e8f5e9" : "#ffebee";

        $data_conclusao = !empty($d['data_conclusao']) 
            ? date("d/m/Y", strtotime($d['data_conclusao'])) 
            : "-";

        $html .= "<tr style='background:$bg;'>
            <td>".date("d/m/Y", strtotime($d['data']))."</td>
            <td>".ucwords(str_replace("_"," ",$d['tipo']))."</td>
            <td>{$d['quantidade']} {$d['unidade']}</td>
            <td style='color:$cor; font-weight:bold;'>".ucwords($d['status'])."</td>
            <td>{$data_conclusao}</td>
        </tr>";
    }

    $html .= "</tbody></table><br>";

    return $html;
}

/* ===============================
📄 HTML BASE
=============================== */

$html = "
<h1>Relatório Fitossanitário</h1>
<p><b>Período:</b> ".date("d/m/Y",strtotime($data_ini))." até ".date("d/m/Y",strtotime($data_fim))."</p>
";

/* ===============================
📊 LOOP AREAS
=============================== */

foreach($dados_por_area as $area => $dados){

    $html .= "<p style='font-size:14px;'><b>Total aplicado:</b><br>";

    foreach($dados['totais'] as $un => $valor){

        if($valor <= 0) continue; // NÃO MOSTRA ZERO

        $valor_formatado = number_format($valor, 3, ',', '.');

        $html .= "<span style='color:#2e7d32; font-weight:bold;'>
            $valor_formatado $un
        </span><br>";
    }

    $html .= "</p>";

        $html .= tabela($dados['pendentes'], "Pendentes");
        $html .= tabela($dados['concluidos'], "Concluídos");

        $html .= "</div>";
    }

/* ===============================
📄 MPDF
=============================== */

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 45,
    'margin_bottom' => 20,
    'tempDir' => __DIR__ . '/../../tmp/mpdf'
]);

/* ===============================
🎨 LOGOS
=============================== */

$logo_frutag = __DIR__ . '/../../img/logo-frutag.png';
$logo_caderno = __DIR__ . '/../../img/logo-color.png';

$img_frutag = file_exists($logo_frutag) ? base64_encode(file_get_contents($logo_frutag)) : '';
$img_caderno = file_exists($logo_caderno) ? base64_encode(file_get_contents($logo_caderno)) : '';

/* ===============================
🧾 HEADER
=============================== */

$mpdf->SetHTMLHeader('
<div style="border-bottom:1px solid #ccc; padding-bottom:5px; font-family:sans-serif;">
    
    <div style="width:33%; float:left;">
        <img src="data:image/png;base64,' . $img_frutag . '" width="110">
    </div>

    <div style="width:34%; float:left; text-align:center; font-weight:bold; font-size:16px; color:#2e7d32;">
        Relatório Fitossanitário<br>
        <span style="font-size:12px; color:#666;">
            Período: '.date("d/m/Y",strtotime($data_ini)).' até '.date("d/m/Y",strtotime($data_fim)).'
        </span>
    </div>

    <div style="width:33%; float:right; text-align:right;">
        <img src="data:image/png;base64,' . $img_caderno . '" width="110">
    </div>

    <div style="clear:both;"></div>
</div>
');

/* ===============================
📄 FOOTER
=============================== */

$mpdf->SetHTMLFooter('
<div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
    Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
</div>
');

/* ===============================
📤 OUTPUT
=============================== */

$mpdf->WriteHTML($html);

header('Content-Type: application/pdf');
$mpdf->Output("relatorio_fitossanitario.pdf","I");