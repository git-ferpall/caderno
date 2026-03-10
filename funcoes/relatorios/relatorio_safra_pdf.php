<?php

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;

session_start();

try {

    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) throw new Exception("Usuário não autenticado");

    $propriedade = $_POST['propriedade'] ?? null;
    $area = $_POST['area'] ?? null;
    $produto = $_POST['produto'] ?? null;
    $data_ini = $_POST['data_ini'] ?? date("Y-01-01");
    $data_fim = $_POST['data_fim'] ?? date("Y-m-d");

    if (!$propriedade) {
        throw new Exception("Propriedade obrigatória");
    }

    /* ==============================
       BUSCAR PLANTIOS
    ============================== */

    $sqlPlantio = "
    SELECT
        a.id,
        a.data,
        a.quantidade,
        ar.nome AS area_nome,
        p.nome AS produto_nome

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

    WHERE a.tipo = 'plantio'
    AND a.propriedade_id = ?
    AND a.data BETWEEN ? AND ?
    ";

    $params = [$propriedade, $data_ini, $data_fim];
    $types = "iss";

    if ($area) {
        $sqlPlantio .= " AND ar.id = ?";
        $params[] = $area;
        $types .= "i";
    }

    if ($produto) {
        $sqlPlantio .= " AND p.id = ?";
        $params[] = $produto;
        $types .= "i";
    }

    $stmt = $mysqli->prepare($sqlPlantio);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $plantios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    /* ==============================
       BUSCAR COLHEITAS
    ============================== */

    $sqlColheita = str_replace("plantio", "colheita", $sqlPlantio);

    $stmt = $mysqli->prepare($sqlColheita);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $colheitas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    /* ==============================
       CALCULAR TOTAIS
    ============================== */

    $totalPlantado = 0;
    foreach ($plantios as $p) {
        $totalPlantado += $p['quantidade'];
    }

    $totalColhido = 0;
    foreach ($colheitas as $c) {
        $totalColhido += $c['quantidade'];
    }

    $produtividade = $totalPlantado > 0
        ? round(($totalColhido / $totalPlantado) * 100, 2)
        : 0;

    /* ==============================
       GERAR PDF
    ============================== */

    $mpdf = new Mpdf([
        'margin_top' => 30
    ]);

    $html = "

    <style>

    body { font-family:sans-serif; }

    table {
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
        color:white;
    }

    </style>

    <h2>Relatório de Safra</h2>

    <b>Período:</b> " . date('d/m/Y', strtotime($data_ini)) . " até " . date('d/m/Y', strtotime($data_fim)) . "<br>

    <br>

    <b>Total Plantado:</b> $totalPlantado<br>
    <b>Total Colhido:</b> $totalColhido<br>
    <b>Produtividade:</b> $produtividade %

    <h3>Plantios</h3>

    <table>

    <tr>
        <th>Data</th>
        <th>Área</th>
        <th>Produto</th>
        <th>Quantidade</th>
    </tr>
    ";

    foreach ($plantios as $p) {

        $html .= "

        <tr>
        <td>" . date('d/m/Y', strtotime($p['data'])) . "</td>
        <td>{$p['area_nome']}</td>
        <td>{$p['produto_nome']}</td>
        <td>{$p['quantidade']}</td>
        </tr>
        ";

    }

    $html .= "</table>";

    $html .= "<h3>Colheitas</h3>";

    $html .= "

    <table>

    <tr>
        <th>Data</th>
        <th>Área</th>
        <th>Produto</th>
        <th>Quantidade</th>
    </tr>
    ";

    foreach ($colheitas as $c) {

        $html .= "

        <tr>
        <td>" . date('d/m/Y', strtotime($c['data'])) . "</td>
        <td>{$c['area_nome']}</td>
        <td>{$c['produto_nome']}</td>
        <td>{$c['quantidade']}</td>
        </tr>
        ";

    }

    $html .= "</table>";

    $mpdf->WriteHTML($html);

    $mpdf->Output("relatorio_safra.pdf", "I");

} catch (Exception $e) {

    echo "Erro: " . $e->getMessage();

}