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
📊 QUERY
=============================== */

$sql = "
SELECT 
    ap.*,
    pr.nome_razao as propriedade_nome,

    (
        SELECT ar.nome
        FROM apontamento_detalhes ad
        LEFT JOIN areas ar 
            ON ar.id = CAST(ad.valor AS UNSIGNED)
        WHERE ad.apontamento_id = ap.id
        AND ad.campo = 'area_id'
        LIMIT 1
    ) as area_nome

FROM apontamentos ap

LEFT JOIN propriedades pr 
    ON pr.id = ap.propriedade_id

WHERE ap.tipo IN ('".implode("','",$tipos)."')
AND ap.propriedade_id IN ($in_props)
AND ap.data BETWEEN '$data_ini' AND '$data_fim'
$filtro_area
ORDER BY propriedade_nome, area_nome, ap.data DESC
";

$res = $mysqli->query($sql);

if(!$res){
    die("Erro SQL: ".$mysqli->error);
}

/* ===============================
🔄 CONVERSÃO UNIDADE
=============================== */

function converterUnidade($quantidade, $unidade){

    $u = strtolower(trim($unidade));

    switch($u){

        case 'ml': return [$quantidade / 1000, 'L'];
        case 'l': return [$quantidade, 'L'];

        case 'ton':
        case 't': return [$quantidade * 1000, 'kg'];

        case 'kg': return [$quantidade, 'kg'];

        default: return [$quantidade, $unidade];
    }
}

/* ===============================
📊 AGRUPAMENTO
=============================== */

$dados = [];

while($row = $res->fetch_assoc()){

    $prop = $row['propriedade_nome'] ?? 'Sem propriedade';
    $area = $row['area_nome'] ?? 'Não informada';

    list($qtd, $unidade) = converterUnidade(
        floatval($row['quantidade']),
        $row['unidade']
    );

    if(!isset($dados[$prop])) $dados[$prop] = [];

    if(!isset($dados[$prop][$area])){
        $dados[$prop][$area] = [
            'pendentes' => [],
            'concluidos' => [],
            'totais' => [],
            'total_registros' => 0,
            'total_concluidos' => 0
        ];
    }

    if(!isset($dados[$prop][$area]['totais'][$unidade])){
        $dados[$prop][$area]['totais'][$unidade] = 0;
    }

    $dados[$prop][$area]['total_registros']++;

    if($row['status'] == 'concluido'){
        $dados[$prop][$area]['concluidos'][] = $row;
        $dados[$prop][$area]['total_concluidos']++;

        $dados[$prop][$area]['totais'][$unidade] += $qtd;
    }else{
        $dados[$prop][$area]['pendentes'][] = $row;
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
    </thead><tbody>";

    foreach($dados as $d){

        $cor = ($d['status']=='concluido') ? "#2e7d32" : "#c62828";
        $bg  = ($d['status']=='concluido') ? "#e8f5e9" : "#ffebee";

        $data_conclusao = !empty($d['data_conclusao']) 
            ? date("d/m/Y", strtotime($d['data_conclusao'])) 
            : "-";

        $html .= "<tr style='background:$bg'>
            <td>".date("d/m/Y", strtotime($d['data']))."</td>
            <td>".ucwords(str_replace("_"," ",$d['tipo']))."</td>
            <td>{$d['quantidade']} {$d['unidade']}</td>
            <td style='color:$cor; font-weight:bold;'>".ucwords($d['status'])."</td>
            <td>$data_conclusao</td>
        </tr>";
    }

    $html .= "</tbody></table><br>";

    return $html;
}

/* ===============================
📄 HTML
=============================== */

$html = "
<h1>Relatório Fitossanitário</h1>
<p><b>Período:</b> ".date("d/m/Y",strtotime($data_ini))." até ".date("d/m/Y",strtotime($data_fim))."</p>
";

/* ===============================
📊 EXIBIÇÃO
=============================== */

foreach($dados as $prop => $areas){

    $html .= "<h1 style='margin-top:20px;'>Propriedade: $prop</h1>";

    foreach($areas as $area => $d){

        $total = $d['total_registros'];
        $ok    = $d['total_concluidos'];

        $ef = $total > 0 ? ($ok/$total)*100 : 0;
        $ef_format = number_format($ef,1,',','.');

        $cor = ($ef>=80) ? "#2e7d32" : (($ef>=50) ? "#f9a825" : "#c62828");

        $html .= "
        <div style='border:1px solid #ddd; border-radius:8px; padding:12px; margin-bottom:20px;'>

            <div style='background:#2e7d32; color:white; padding:10px; border-radius:5px;'>
                Área: <b>$area</b>
            </div>

            <p style='margin-top:10px;'>
                <b>Eficiência:</b> 
                <span style='color:$cor; font-weight:bold;'>$ef_format%</span>
            </p>

            <p><b>Total aplicado:</b><br>
        ";

        foreach($d['totais'] as $un => $valor){

            if($valor <= 0) continue;

            $valor_formatado = ($un=='L')
                ? number_format($valor,3,',','.')
                : number_format($valor,2,',','.');

            $html .= "<span style='color:#2e7d32; font-weight:bold;'>
                $valor_formatado $un
            </span><br>";
        }

        $html .= "</p>";

        $html .= tabela($d['pendentes'], "Pendentes");
        $html .= tabela($d['concluidos'], "Concluídos");

        $html .= "</div>";
    }
}

/* ===============================
📄 MPDF
=============================== */

$mpdf = new Mpdf([
    'mode'=>'utf-8',
    'format'=>'A4',
    'margin_top'=>45,
    'margin_bottom'=>20,
    'tempDir'=>__DIR__.'/../../tmp/mpdf'
]);

$mpdf->WriteHTML($html);

header('Content-Type: application/pdf');
$mpdf->Output("relatorio_fitossanitario.pdf","I");