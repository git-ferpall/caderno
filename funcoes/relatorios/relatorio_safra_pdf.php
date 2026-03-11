<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../configuracao/configuracao_conexao.php';
require_once __DIR__.'/../../vendor/autoload.php';

date_default_timezone_set("America/Sao_Paulo");


/* =====================================================
FILTROS
===================================================== */

$propriedade = $_POST['propriedade'] ?? null;
$area        = $_POST['area'] ?? null;
$produto     = $_POST['produto'] ?? null;
$data_ini    = $_POST['data_ini'] ?? null;
$data_fim    = $_POST['data_fim'] ?? null;


/* =====================================================
BUSCAR NOMES
===================================================== */

$nome_propriedade='';
$nome_area='';
$nome_produto='';

if($propriedade){
    $r=$mysqli->query("SELECT nome_razao FROM propriedades WHERE id=$propriedade")->fetch_assoc();
    $nome_propriedade=$r['nome_razao'] ?? '';
}

if($area){
    $r=$mysqli->query("SELECT nome FROM areas WHERE id=$area")->fetch_assoc();
    $nome_area=$r['nome'] ?? '';
}

if($produto){
    $r=$mysqli->query("SELECT nome FROM produtos WHERE id=$produto")->fetch_assoc();
    $nome_produto=$r['nome'] ?? '';
}


/* =====================================================
SQL
===================================================== */

$sql="

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
    $sql.=" AND a.propriedade_id=$propriedade";
}

if($data_ini){
    $sql.=" AND a.data>='$data_ini'";
}

if($data_fim){
    $sql.=" AND a.data<='$data_fim'";
}

$sql.="

GROUP BY a.id
ORDER BY a.data

";

$res=$mysqli->query($sql);

$dados=[];

while($row=$res->fetch_assoc()){

    if($area && $row['area_id']!=$area){
        continue;
    }

    if($produto && $row['produto_id']!=$produto){
        continue;
    }

    /* PRODUTO */

    $row['produto_nome']='';

    if($row['produto_id']){

        $p=$mysqli->query("SELECT nome FROM produtos WHERE id=".$row['produto_id'])->fetch_assoc();
        $row['produto_nome']=$p['nome'] ?? '';

    }

    /* AREA */

    $row['area_m2']=0;
    $row['area_ha']=0;

    if($row['area_id']){

        $a=$mysqli->query("SELECT tamanho FROM areas WHERE id=".$row['area_id'])->fetch_assoc();

        if($a){

            $row['area_m2']=$a['tamanho'];
            $row['area_ha']=$a['tamanho']/10000;

        }

    }

    $dados[]=$row;

}


/* =====================================================
IDENTIFICAR SAFRAS
===================================================== */

$safras=[];
$plantio=null;

foreach($dados as $d){

    if($d['tipo']=="plantio"){
        $plantio=$d;
    }

    if($d['tipo']=="colheita" && $plantio){

        $produtividade=0;
        $prod_ha=0;

        if($plantio['quantidade']>0){
            $produtividade=$d['quantidade']/$plantio['quantidade'];
        }

        if($d['area_ha']>0){
            $prod_ha=$d['quantidade']/$d['area_ha'];
        }

        $safras[]=[

            "produto"=>$d['produto_nome'],
            "plantio"=>$plantio['data'],
            "colheita"=>$d['data'],
            "plantado"=>$plantio['quantidade'],
            "colhido"=>$d['quantidade'],
            "prod"=>$produtividade,
            "prod_ha"=>$prod_ha,
            "area"=>$d['area_ha'],
            "unidade"=>$d['unidade']

        ];

        $plantio=null;

    }

}


/* =====================================================
GRAFICO
===================================================== */

$grafico=[];

foreach($safras as $s){
    $grafico[]=round($s['prod_ha'],2);
}


/* =====================================================
REFERENCIA BRASIL
===================================================== */

$media=0;
$min=0;
$max=0;

$stmt = $mysqli->prepare("
SELECT prod_min, prod_media, prod_max, unidade
FROM produtividade_referencia
WHERE LOWER(produto) LIKE CONCAT('%',LOWER(?),'%')
LIMIT 1
");

$stmt->bind_param("s",$nome_produto);
$stmt->execute();

$ref=$stmt->get_result()->fetch_assoc();

if($ref){

    $min   = $ref['prod_min'];
    $media = $ref['prod_media'];
    $max   = $ref['prod_max'];

}


/* =====================================================
CORES
===================================================== */

$cores=[];

foreach($grafico as $g){
    $cores[]=sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}


/* =====================================================
FAIXAS PRODUTIVIDADE
===================================================== */

$faixa_ruim  = $min;
$faixa_media = $media;
$faixa_boa   = $max;

$faixa_max   = max(max($grafico), $max) * 1.2;


/* =====================================================
GRAFICO QUICKCHART
===================================================== */

$chartConfig = [

"type" => "bar",

"data" => [

"labels" => array_map(function($i){
    return "Safra ".($i+1);
}, array_keys($grafico)),

"datasets" => [

[
"type"=>"bar",
"label"=>"Produtividade (sacas/ha)",
"data"=>$grafico,
"backgroundColor"=>$cores
],

[
"type"=>"line",
"label"=>"Média Brasil",
"data"=>array_fill(0,count($grafico),$media),
"borderColor"=>"#1e88e5",
"borderWidth"=>3,
"fill"=>false
],

[
"type"=>"bar",
"label"=>"Faixa baixa",
"data"=>array_fill(0,count($grafico),$faixa_ruim),
"backgroundColor"=>"rgba(255,152,0,0.15)",
"stack"=>"bg"
],

[
"type"=>"bar",
"label"=>"Faixa média",
"data"=>array_fill(0,count($grafico),$faixa_media-$faixa_ruim),
"backgroundColor"=>"rgba(76,175,80,0.15)",
"stack"=>"bg"
],

[
"type"=>"bar",
"label"=>"Faixa alta",
"data"=>array_fill(0,count($grafico),$faixa_max-$faixa_media),
"backgroundColor"=>"rgba(33,150,243,0.15)",
"stack"=>"bg"
]

]

],

"options" => [

"scales"=>[

"x"=>[
"stacked"=>true
],

"y"=>[
"beginAtZero"=>true,
"max"=>$faixa_max
]

]

]

];

$chartUrl="https://quickchart.io/chart?c=".urlencode(json_encode($chartConfig));


/* =====================================================
MPDF
===================================================== */

$tempDir=__DIR__.'/../../tmp/mpdf';

if(!is_dir($tempDir)){
    mkdir($tempDir,0777,true);
}

$mpdf=new \Mpdf\Mpdf([
'mode'=>'utf-8',
'format'=>'A4',
'tempDir'=>$tempDir
]);


/* =====================================================
HTML
===================================================== */

$html="

<style>

body{
font-family:Arial;
font-size:12px;
}

h1{
text-align:center;
color:#2e7d32;
}

table{
border-collapse:collapse;
width:100%;
margin-top:10px;
}

th{
background:#4caf50;
color:#fff;
padding:6px;
}

td{
border:1px solid #ccc;
padding:6px;
text-align:center;
}

</style>

<h1>Relatório de Produtividade</h1>

<b>Propriedade:</b> $nome_propriedade<br>
<b>Área:</b> $nome_area<br>
<b>Produto:</b> $nome_produto<br>
<b>Período:</b> ".date('d/m/Y',strtotime($data_ini))." até ".date('d/m/Y',strtotime($data_fim))."

<br><br>

<img src='$chartUrl' style='width:100%'>

<table>

<tr>
<th>Safra</th>
<th>Produto</th>
<th>Plantio</th>
<th>Colheita</th>
<th>Área (ha)</th>
<th>Produção</th>
<th>Produtividade</th>
<th>Prod/ha</th>
</tr>

";

$i=1;

foreach($safras as $r){

$html.="

<tr>

<td>Safra $i</td>
<td>{$r['produto']}</td>
<td>".date('d/m/Y',strtotime($r['plantio']))."</td>
<td>".date('d/m/Y',strtotime($r['colheita']))."</td>
<td>".number_format($r['area'],2)."</td>
<td>{$r['colhido']}</td>
<td>".number_format($r['prod'],2)." {$r['unidade']}</td>
<td>".number_format($r['prod_ha'],2)." {$r['unidade']}/ha</td>

</tr>

";

$i++;

}

$html.="

</table>

<br>

<b>Referência nacional</b><br>
Produtividade mínima: $min<br>
Produtividade média: $media<br>
Produtividade máxima: $max

";

$mpdf->WriteHTML($html);
$mpdf->Output("relatorio_safra.pdf","I");