<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';

date_default_timezone_set("America/Sao_Paulo");

/* ===============================
FILTROS
=============================== */

$propriedade = $_POST['propriedade'] ?? null;
$area        = $_POST['area'] ?? null;
$produto     = $_POST['produto'] ?? null;
$data_ini    = $_POST['data_ini'] ?? null;
$data_fim    = $_POST['data_fim'] ?? null;


/* ===============================
BUSCA NOMES
=============================== */

$nome_propriedade = '';
$nome_area = '';
$nome_produto = '';

if($propriedade){
    $r = $mysqli->query("SELECT nome_razao FROM propriedades WHERE id=$propriedade")->fetch_assoc();
    $nome_propriedade = $r['nome_razao'] ?? '';
}

if($area){
    $r = $mysqli->query("SELECT nome FROM areas WHERE id=$area")->fetch_assoc();
    $nome_area = $r['nome'] ?? '';
}

if($produto){
    $r = $mysqli->query("SELECT nome FROM produtos WHERE id=$produto")->fetch_assoc();
    $nome_produto = $r['nome'] ?? '';
}


/* ===============================
SQL
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

";

if($propriedade){
$sql .= " AND a.propriedade_id = $propriedade";
}

if($data_ini){
$sql .= " AND a.data >= '$data_ini'";
}

if($data_fim){
$sql .= " AND a.data <= '$data_fim'";
}

$sql .= "

GROUP BY a.id
ORDER BY a.data

";

$res = $mysqli->query($sql);

$dados = [];

while($row = $res->fetch_assoc()){

    if($area && $row['area_id'] != $area){
        continue;
    }

    if($produto && $row['produto_id'] != $produto){
        continue;
    }

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

            "data_plantio"=>$plantio['data'],
            "data_colheita"=>$d['data'],
            "plantado"=>$plantio['quantidade'],
            "colhido"=>$d['quantidade'],
            "produtividade"=>$produtividade,
            "unidade"=>$d['unidade']

        ];

        $plantio = null;
    }

}


/* ===============================
MPDF
=============================== */

$tempDir = __DIR__ . '/../../tmp/mpdf';

if(!is_dir($tempDir)){
mkdir($tempDir,0777,true);
}

$mpdf = new \Mpdf\Mpdf([
'mode'=>'utf-8',
'format'=>'A4',
'tempDir'=>$tempDir
]);


/* ===============================
HTML
=============================== */

$html = "

<style>

body{
font-family:Arial;
font-size:12px;
}

h1{
text-align:center;
color:#2e7d32;
margin-bottom:5px;
}

.info{
margin-bottom:20px;
}

table{
border-collapse:collapse;
width:100%;
}

th{
background:#4caf50;
color:#fff;
padding:8px;
}

td{
border:1px solid #ccc;
padding:6px;
text-align:center;
}

</style>

<h1>Relatório de Safra</h1>

<div class='info'>

<b>Propriedade:</b> $nome_propriedade<br>
<b>Área:</b> $nome_area<br>
<b>Produto:</b> $nome_produto<br>
<b>Período:</b> ".date('d/m/Y',strtotime($data_ini))." até ".date('d/m/Y',strtotime($data_fim))."

</div>

<table>

<tr>
<th>Safra</th>
<th>Produto</th>
<th>Plantio</th>
<th>Colheita</th>
<th>Plantado</th>
<th>Produção</th>
<th>Produtividade</th>
</tr>

";

$s=1;

foreach($safras as $r){

$html.="

<tr>

<td>Safra $s</td>
<td>$nome_produto</td>
<td>".date('d/m/Y',strtotime($r['data_plantio']))."</td>
<td>".date('d/m/Y',strtotime($r['data_colheita']))."</td>
<td>{$r['plantado']}</td>
<td>{$r['colhido']}</td>
<td>".number_format($r['produtividade'],2)." {$r['unidade']}</td>

</tr>

";

$s++;

}

$html.="</table>";

$mpdf->WriteHTML($html);
$mpdf->Output("relatorio_safra.pdf","I");