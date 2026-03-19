<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../configuracao/configuracao_conexao.php';
require_once __DIR__.'/../../vendor/autoload.php';

use Mpdf\Mpdf;

$data_ini = $_POST['data_ini'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;
$areas    = $_POST['area'] ?? null;
$props    = $_POST['propriedade'] ?? [];

if (!is_array($props)) $props = [$props];

if (!$data_ini || !$data_fim || empty($props)) {
    die("Parâmetros inválidos");
}

$tipos = [
    'inseticida',
    'herbicida',
    'fertilizante',
    'fungicida',
    'adubacao_organica',
    'adubacao_calcario'
];

$in_props = implode(",", array_map('intval', $props));

$filtro_area = "";
if (!empty($areas)) {
    $filtro_area = "AND EXISTS (
        SELECT 1 FROM apontamento_detalhes ad
        WHERE ad.apontamento_id = ap.id
        AND ad.campo = 'area_id'
        AND ad.valor = '".intval($areas)."'
    )";
}

$sql = "
SELECT 
    ap.id,
    ap.tipo,
    ap.data,
    ap.quantidade,
    ap.unidade,
    ap.status
FROM apontamentos ap
WHERE ap.tipo IN ('".implode("','",$tipos)."')
AND ap.propriedade_id IN ($in_props)
AND ap.data BETWEEN '$data_ini' AND '$data_fim'
$filtro_area
ORDER BY ap.data DESC
";

$res = $mysqli->query($sql);

$pendentes = [];
$concluidos = [];

while($row = $res->fetch_assoc()){

    if($row['status'] == 'concluido'){
        $concluidos[] = $row;
    }else{
        $pendentes[] = $row;
    }
}

function tabela($dados, $titulo){

    $html = "<h2>$titulo</h2>";

    if(empty($dados)){
        return $html."<p>Nenhum registro</p>";
    }

    $html .= "<table border='1' width='100%' cellspacing='0' cellpadding='6'>
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

    foreach($dados as $d){

        $html .= "<tr>
            <td>".date("d/m/Y", strtotime($d['data']))."</td>
            <td>".ucwords(str_replace("_"," ",$d['tipo']))."</td>
            <td>{$d['quantidade']} {$d['unidade']}</td>
            <td>{$d['status']}</td>
        </tr>";
    }

    $html .= "</tbody></table><br>";

    return $html;
}

$html = "
<h1>Relatório Fitossanitário</h1>
<p>Período: ".date("d/m/Y",strtotime($data_ini))." até ".date("d/m/Y",strtotime($data_fim))."</p>
";

$html .= tabela($pendentes, "Pendentes");
$html .= tabela($concluidos, "Concluídos");

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
📄 FOOTER (PAGINAÇÃO)
=============================== */

$mpdf->SetHTMLFooter('
<div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
    Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
</div>
');

/* ===============================
📑 CONTEÚDO
=============================== */

$mpdf->WriteHTML($html);

/* ===============================
📤 OUTPUT
=============================== */

header('Content-Type: application/pdf');
$mpdf->Output("relatorio_fitossanitario.pdf","I");