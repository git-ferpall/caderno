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

    $row['area_m2'] = 0;
    $row['area_ha'] = 0;

    if($row['area_id']){

        $a = $mysqli->query("SELECT tamanho FROM areas WHERE id=".$row['area_id'])->fetch_assoc();

        if($a){

            $row['area_m2'] = floatval($a['tamanho']);
            $row['area_ha'] = $row['area_m2'] / 10000;

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

        $produtividade = 0;
        $prod_area = 0;
        $unidade_area = "ha";

        /* produtividade por planta */

        if($plantio['quantidade'] > 0){
            $produtividade = $d['quantidade'] / $plantio['quantidade'];
        }

        /* produtividade por área */

        if($d['area_m2'] > 0){

            if($d['area_m2'] < 1000){

                $prod_area = $d['quantidade'] / $d['area_m2'];
                $unidade_area = "m²";

            }
            else{

                $prod_area = $d['quantidade'] / $d['area_ha'];
                $unidade_area = "ha";

            }

        }

        $safras[]=[

            "produto"=>$d['produto_nome'],
            "plantio"=>$plantio['data'],
            "colheita"=>$d['data'],
            "plantado"=>$plantio['quantidade'],
            "colhido"=>$d['quantidade'],

            "prod"=>$produtividade,

            "prod_area"=>$prod_area,
            "un_area"=>$unidade_area,

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
    $grafico[]=round($s['prod_area'],2);
}

/* =====================================================
EVOLUÇÃO ENTRE SAFRAS
===================================================== */

$evolucao = [];
$comparacoes = [];

if(count($grafico) > 1){

    for($i=1;$i<count($grafico);$i++){

        $anterior = $grafico[$i-1];
        $atual    = $grafico[$i];

        $perc = 0;

        if($anterior > 0){
            $perc = (($atual - $anterior) / $anterior) * 100;
        }

        $evolucao[] = round($perc,2);

        $comparacoes[] = [
            "safra_anterior"=>$anterior,
            "safra_atual"=>$atual,
            "perc"=>$perc
        ];
    }

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

$maiorGrafico = 0;

if(count($grafico) > 0){
    $maiorGrafico = max($grafico);
}

$faixa_max = max($maiorGrafico, $max) * 1.2;


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
"label"=>"Produtividade",
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
LIMITES DO GRAFICO EVOLUÇÃO
===================================================== */

$minGraf = 0;
$maxGraf = 10;

if(count($evolucao) > 0){

    $minEvo = min($evolucao);
    $maxEvo = max($evolucao);

    $minGraf = min(0, $minEvo * 1.2);
    $maxGraf = max(5, $maxEvo * 1.2);

}

/* =====================================================
GRAFICO EVOLUÇÃO ENTRE SAFRAS
===================================================== */

$chartUrlEvolucao = null;

if(count($evolucao) > 0){

$chartConfig2 = [

"type" => "bar",

"data" => [

"labels" => array_map(function($i){
    return "Safra ".($i+1)." → ".($i+2);
}, array_keys($evolucao)),

"datasets" => [

[
"label"=>"Evolução %",
"data"=>$evolucao,
"backgroundColor"=>array_map(function($v){
    return $v >= 0 ? "#4caf50" : "#e53935";
}, $evolucao)
]

]

],

"options"=>[
"scales"=>[
"y"=>[
"min"=>$minGraf,
"max"=>$maxGraf,
"title"=>[
"display"=>true,
"text"=>"Variação %"
]
]
]
]

];

$chartUrlEvolucao="https://quickchart.io/chart?c=".urlencode(json_encode($chartConfig2));

}
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
'margin_top'=>35,
'margin_bottom'=>20,
'margin_left'=>10,
'margin_right'=>10,
'tempDir'=>$tempDir
]);


/* =====================================================
HTML
===================================================== */

$html = "

<style>

body{
    font-family: Arial;
    font-size: 12px;
}

h1{
    text-align: center;
    color: #2e7d32;
}

h2{
    color: #2e7d32;
    margin-top: 25px;
}

table{
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
}

th{
    background: #4caf50;
    color: #fff;
    padding: 6px;
}

td{
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
}

.info{
    margin-bottom: 15px;
}

</style>

<h1>Relatório de Safras</h1>

<div class='info'>

<b>Propriedade:</b> {$nome_propriedade}<br>
<b>Área:</b> {$nome_area}<br>
<b>Produto:</b> {$nome_produto}<br>
<b>Período:</b> ".date('d/m/Y',strtotime($data_ini))." até ".date('d/m/Y',strtotime($data_fim))."

</div>

<img src='{$chartUrl}' style='width:100%'>

<h2>Safras registradas</h2>

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

$i = 1;

foreach($safras as $r){

    $html .= "

    <tr>

        <td>Safra {$i}</td>
        <td>{$r['produto']}</td>

        <td>".date('d/m/Y',strtotime($r['plantio']))."</td>

        <td>".date('d/m/Y',strtotime($r['colheita']))."</td>

        <td>
            ".(
                $r['area'] < 0.01
                ? number_format($r['area']*10000,0)." m²"
                : number_format($r['area'],2)." ha"
            )."
            </td>

        <td>{$r['colhido']}</td>

        <td>".number_format($r['prod'],2)." {$r['unidade']}</td>

        <td>".number_format($r['prod_area'],2)." {$r['unidade']}/{$r['un_area']}</td>

    </tr>

    ";

    $i++;

}

$html .= "

</table>

<br>

<b>Referência nacional</b><br>

Produtividade mínima: {$min}<br>
Produtividade média: {$media}<br>
Produtividade máxima: {$max}<br>

";


/* =====================================================
EVOLUÇÃO ENTRE SAFRAS
===================================================== */

if(isset($chartUrlEvolucao) && $chartUrlEvolucao){

    $html .= "

    <h2>Evolução entre safras</h2>

    <img src='{$chartUrlEvolucao}' style='width:100%'>

    ";

}


/* =====================================================
COMPARAÇÃO ENTRE SAFRAS
===================================================== */

if(isset($comparacoes) && count($comparacoes) > 0){

    $html .= "<h2>Comparativo entre safras</h2>";

    $i = 1;

    foreach($comparacoes as $c){

        $seta = $c['perc'] >= 0 ? "⬆ aumento" : "⬇ redução";

        $html .= "

        Safra {$i} → Safra ".($i+1)."<br>

        Produtividade anterior:
        ".number_format($c['safra_anterior'],2)." sacas/ha<br>

        Produtividade atual:
        ".number_format($c['safra_atual'],2)." sacas/ha<br>

        {$seta} de ".number_format($c['perc'],1)." %<br><br>

        ";

        $i++;

    }

}

$logo_frutag  = __DIR__ . '/../../img/logo-frutag.png';
$logo_caderno = __DIR__ . '/../../img/logo-color.png';

$img_frutag  = file_exists($logo_frutag) ? base64_encode(file_get_contents($logo_frutag)) : '';
$img_caderno = file_exists($logo_caderno) ? base64_encode(file_get_contents($logo_caderno)) : '';

$mpdf->SetHTMLHeader('
<table width="100%" style="border-bottom:1px solid #ccc; font-family:Arial; font-size:10px;">
<tr>

<td width="33%">
<img src="data:image/png;base64,'.$img_frutag.'" width="110">
</td>

<td width="34%" style="text-align:center; font-weight:bold; font-size:16px; color:#2e7d32;">
Relatório de Produtividade<br>
<span style="font-size:11px; color:#666;">Caderno de Campo</span>
</td>

<td width="33%" style="text-align:right;">
<img src="data:image/png;base64,'.$img_caderno.'" width="110">
</td>

</tr>
</table>
');
$mpdf->WriteHTML($html);
$mpdf->Output('relatorio_safra.pdf', 'I');