<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set("America/Sao_Paulo");

/* ===============================
FILTROS
=============================== */

$propriedade = $_POST['propriedade'] ?? null;
$area = $_POST['area'] ?? null;
$produto = $_POST['produto'] ?? null;
$data_ini = $_POST['data_ini'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;


/* ===============================
SQL BASE
=============================== */

$sql = "

SELECT
a.id,
a.tipo,
a.data,
a.quantidade,
a.unidade,

MAX(CASE WHEN d.campo='area_id' THEN d.valor END) area_id,
MAX(CASE WHEN d.campo='produto_id' THEN d.valor END) produto_id

FROM apontamentos a

LEFT JOIN apontamento_detalhes d
ON d.apontamento_id = a.id

WHERE a.tipo IN ('plantio','colheita')
AND a.status='concluido'

GROUP BY a.id

ORDER BY a.data

";

$res = $mysqli->query($sql);

$dados = [];

while($row = $res->fetch_assoc()){
    $dados[] = $row;
}


/* ===============================
IDENTIFICAR SAFRAS
=============================== */

$safras = [];

$plantio = null;

foreach($dados as $d){

    if($d['tipo'] == 'plantio'){

        $plantio = $d;

    }

    if($d['tipo'] == 'colheita' && $plantio){

        $produtividade = 0;

        if($plantio['quantidade'] > 0){
            $produtividade = $d['quantidade'] / $plantio['quantidade'];
        }

        $safras[] = [

            "data_plantio" => $plantio['data'],
            "data_colheita" => $d['data'],
            "plantado" => $plantio['quantidade'],
            "colhido" => $d['quantidade'],
            "produtividade" => $produtividade,
            "unidade" => $d['unidade']

        ];

        $plantio = null;

    }

}


/* ===============================
GERAR PDF
=============================== */

$mpdf = new \Mpdf\Mpdf();

$html = "

<h1>Relatório de Safra</h1>

<style>
table{
border-collapse:collapse;
width:100%;
}
td,th{
border:1px solid #ccc;
padding:6px;
text-align:center;
}
</style>

<table>

<tr>
<th>Safra</th>
<th>Plantio</th>
<th>Colheita</th>
<th>Plantado</th>
<th>Produção</th>
<th>Produtividade</th>
</tr>

";

$s = 1;

foreach($safras as $r){

$html .= "

<tr>
<td>Safra {$s}</td>
<td>{$r['data_plantio']}</td>
<td>{$r['data_colheita']}</td>
<td>{$r['plantado']}</td>
<td>{$r['colhido']}</td>
<td>".number_format($r['produtividade'],2)." {$r['unidade']}</td>
</tr>

";

$s++;

}

$html .= "</table>";

$mpdf->WriteHTML($html);

$mpdf->Output("relatorio_safra.pdf","I");