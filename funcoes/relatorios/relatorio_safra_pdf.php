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
$area        = $_POST['area'] ?? null;
$produto     = $_POST['produto'] ?? null;
$data_ini    = $_POST['data_ini'] ?? null;
$data_fim    = $_POST['data_fim'] ?? null;


/* ===============================
SQL BASE COM FILTROS
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

$params = [];
$types  = "";

/* FILTRO PROPRIEDADE */

if(!empty($propriedade)){
    $sql .= " AND a.propriedade_id = ?";
    $params[] = $propriedade;
    $types .= "i";
}

/* FILTRO DATA */

if(!empty($data_ini)){
    $sql .= " AND a.data >= ?";
    $params[] = $data_ini;
    $types .= "s";
}

if(!empty($data_fim)){
    $sql .= " AND a.data <= ?";
    $params[] = $data_fim;
    $types .= "s";
}

$sql .= "
GROUP BY a.id
ORDER BY a.data
";

/* EXECUTA QUERY */

$stmt = $mysqli->prepare($sql);

if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$dados = [];

while($row = $res->fetch_assoc()){

    /* FILTRO AREA */

    if(!empty($area) && $row['area_id'] != $area){
        continue;
    }

    /* FILTRO PRODUTO */

    if(!empty($produto) && $row['produto_id'] != $produto){
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

            "data_plantio"  => $plantio['data'],
            "data_colheita" => $d['data'],
            "plantado"      => $plantio['quantidade'],
            "colhido"       => $d['quantidade'],
            "produtividade" => $produtividade,
            "unidade"       => $d['unidade']

        ];

        $plantio = null;

    }

}


/* ===============================
CONFIGURA MPDF
=============================== */

$tempDir = __DIR__ . '/../../tmp/mpdf';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => $tempDir
]);


/* ===============================
HTML DO RELATÓRIO
=============================== */

$html = "

<h1>Relatório de Safra</h1>

<p>

<b>Propriedade:</b> {$propriedade}<br>
<b>Área:</b> {$area}<br>
<b>Produto:</b> {$produto}<br>
<b>Período:</b> ".date('d/m/Y',strtotime($data_ini))." até ".date('d/m/Y',strtotime($data_fim))."

</p>

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

th{
    background:#4caf50;
    color:#fff;
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
<td>".date('d/m/Y',strtotime($r['data_plantio']))."</td>
<td>".date('d/m/Y',strtotime($r['data_colheita']))."</td>
<td>{$r['plantado']}</td>
<td>{$r['colhido']}</td>
<td>".number_format($r['produtividade'],2)." {$r['unidade']}</td>
</tr>

";

$s++;

}

$html .= "</table>";


/* ===============================
GERAR PDF
=============================== */

$mpdf->WriteHTML($html);

$mpdf->Output("relatorio_safra.pdf","I");