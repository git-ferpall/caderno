<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;

session_start();

function paraLitros($quantidade, $unidade)
{
    $q = (float)$quantidade;
    $u = strtolower(trim((string)$unidade));

    switch ($u) {
        case 'ml':
            return $q / 1000;
        case 'l':
            return $q;
        case 'm3':
            return $q * 1000;
        default:
            return $q;
    }
}

function periodoDias($dataIni, $dataFim)
{
    try {
        $ini = new DateTime($dataIni);
        $fim = new DateTime($dataFim);
        $dias = (int)$ini->diff($fim)->days + 1; // inclusivo
        return $dias > 0 ? $dias : 1;
    } catch (Throwable $e) {
        return 1;
    }
}

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    $propriedade_id = (int)($_POST['propriedade'] ?? 0);
    $data_ini = $_POST['data_ini'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $areasPost = $_POST['area'] ?? [];

    if (!is_array($areasPost)) {
        $areasPost = [$areasPost];
    }
    $area_ids = array_values(array_unique(array_filter(array_map('intval', $areasPost))));

    if ($propriedade_id <= 0 || !$data_ini || !$data_fim || empty($area_ids)) {
        throw new Exception('parametros_invalidos');
    }

    $stmtProp = $mysqli->prepare("SELECT id, nome_razao FROM propriedades WHERE id = ? AND user_id = ? LIMIT 1");
    $stmtProp->bind_param('ii', $propriedade_id, $user_id);
    $stmtProp->execute();
    $propriedade = $stmtProp->get_result()->fetch_assoc();
    $stmtProp->close();
    if (!$propriedade) {
        throw new Exception('propriedade_invalida');
    }

    $inAreas = implode(',', $area_ids);
    $sqlAreas = "
        SELECT id, nome, tamanho
        FROM areas
        WHERE propriedade_id = ? AND id IN ($inAreas)
        ORDER BY nome
    ";
    $stmtAreas = $mysqli->prepare($sqlAreas);
    $stmtAreas->bind_param('i', $propriedade_id);
    $stmtAreas->execute();
    $rowsAreas = $stmtAreas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtAreas->close();
    if (empty($rowsAreas)) {
        throw new Exception('nenhuma_area_valida');
    }

    $areasInfo = [];
    $totalAreaHa = 0.0;
    $totalAreaM2 = 0.0;
    foreach ($rowsAreas as $a) {
        $id = (int)$a['id'];
        $m2 = (float)$a['tamanho'];
        $ha = $m2 / 10000;
        $haFinal = $ha > 0 ? $ha : 0;
        $areasInfo[$id] = [
            'nome' => $a['nome'],
            'm2' => $m2 > 0 ? $m2 : 0,
            'ha' => $haFinal,
            'volume_l' => 0.0,
            'registros' => 0
        ];
        $totalAreaHa += $haFinal;
        $totalAreaM2 += ($m2 > 0 ? $m2 : 0);
    }

    $sqlApts = "
        SELECT ap.id, ap.data, ap.quantidade, ap.unidade, ap.status, ad.valor AS area_id
        FROM apontamentos ap
        JOIN apontamento_detalhes ad
          ON ad.apontamento_id = ap.id AND ad.campo = 'area_id'
        WHERE ap.tipo = 'irrigacao'
          AND ap.status = 'concluido'
          AND ap.propriedade_id = ?
          AND ap.data BETWEEN ? AND ?
          AND CAST(ad.valor AS UNSIGNED) IN ($inAreas)
        ORDER BY ap.data ASC, ap.id ASC
    ";
    $stmtApt = $mysqli->prepare($sqlApts);
    $stmtApt->bind_param('iss', $propriedade_id, $data_ini, $data_fim);
    $stmtApt->execute();
    $rows = $stmtApt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtApt->close();

    $apontamentos = [];
    $evolucaoMensal = [];
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        if (!isset($apontamentos[$id])) {
            $apontamentos[$id] = [
                'id' => $id,
                'data' => $r['data'],
                'quantidade' => (float)$r['quantidade'],
                'unidade' => $r['unidade'],
                'status' => $r['status'],
                'areas' => []
            ];
        }
        $aid = (int)$r['area_id'];
        if (isset($areasInfo[$aid])) {
            $apontamentos[$id]['areas'][$aid] = true;
        }
    }

    $totalRegistros = 0;
    foreach ($apontamentos as $apt) {
        $areasDoRegistro = array_keys($apt['areas']);
        $countAreas = count($areasDoRegistro);
        if ($countAreas === 0) {
            continue;
        }

        $volumeLitrosTotal = paraLitros($apt['quantidade'], $apt['unidade']);
        $volumeRateado = $volumeLitrosTotal / $countAreas;
        $totalRegistros++;

        $mesRef = date('Y-m', strtotime($apt['data']));
        if (!isset($evolucaoMensal[$mesRef])) {
            $evolucaoMensal[$mesRef] = [
                'volume_l' => 0.0,
                'registros' => 0
            ];
        }
        $evolucaoMensal[$mesRef]['volume_l'] += $volumeLitrosTotal;
        $evolucaoMensal[$mesRef]['registros']++;

        foreach ($areasDoRegistro as $aid) {
            $areasInfo[$aid]['volume_l'] += $volumeRateado;
            $areasInfo[$aid]['registros']++;
        }
    }

    $comparativo = [];
    $somaLHa = 0.0;
    $countLHa = 0;
    foreach ($areasInfo as $id => $info) {
        $lha = ($info['ha'] > 0) ? ($info['volume_l'] / $info['ha']) : 0;
        if ($info['ha'] > 0) {
            $somaLHa += $lha;
            $countLHa++;
        }
        $comparativo[] = [
            'nome' => $info['nome'],
            'm2' => $info['m2'],
            'ha' => $info['ha'],
            'volume_l' => $info['volume_l'],
            'lha' => $lha,
            'registros' => $info['registros']
        ];
    }

    usort($comparativo, function ($a, $b) {
        return $b['lha'] <=> $a['lha'];
    });

    $diasPeriodo = periodoDias($data_ini, $data_fim);
    $mediaLHa = $countLHa > 0 ? ($somaLHa / $countLHa) : 0;
    $mediaLHaDia = $mediaLHa / $diasPeriodo;
    $totalLitros = array_reduce($comparativo, function ($carry, $item) {
        return $carry + $item['volume_l'];
    }, 0.0);

    foreach ($comparativo as &$linha) {
        $linha['participacao'] = $totalLitros > 0 ? (($linha['volume_l'] / $totalLitros) * 100) : 0;
        $linha['lha_dia'] = $diasPeriodo > 0 ? ($linha['lha'] / $diasPeriodo) : 0;
        $linha['lm2'] = $linha['m2'] > 0 ? ($linha['volume_l'] / $linha['m2']) : 0;
    }
    unset($linha);

    ksort($evolucaoMensal);

    $html = '<h1>Relatorio de Irrigacao</h1>';
    $html .= '<p><b>Propriedade:</b> ' . htmlspecialchars($propriedade['nome_razao']) . '</p>';
    $html .= '<p><b>Periodo:</b> ' . date('d/m/Y', strtotime($data_ini)) . ' ate ' . date('d/m/Y', strtotime($data_fim)) . '</p>';
    $html .= '<p><b>Dias no periodo:</b> ' . $diasPeriodo . '</p>';
    $html .= '<p><b>Registros considerados:</b> ' . $totalRegistros . '</p>';
    $html .= '<p><b>Volume total irrigado (rateado entre areas do mesmo apontamento):</b> ' . number_format($totalLitros, 2, ',', '.') . ' L</p>';
    $html .= '<p><b>Area total selecionada:</b> ' . number_format($totalAreaM2, 2, ',', '.') . ' m2 (' . number_format($totalAreaHa, 4, ',', '.') . ' ha)</p>';
    $html .= '<p><b>Media geral:</b> ' . number_format($mediaLHa, 2, ',', '.') . ' L/ha</p>';
    $html .= '<p><b>Eficiencia hidrica por periodo:</b> ' . number_format($mediaLHaDia, 2, ',', '.') . ' L/ha/dia</p>';
    $html .= '<p><b>Filtro aplicado:</b> apenas registros concluidos.</p>';

    if (count($comparativo) > 1) {
        $html .= '<p><b>Ranking:</b> areas ordenadas por maior consumo especifico (L/ha).</p>';
    }

    $html .= "
    <table border='1' width='100%' cellspacing='0' cellpadding='6'>
      <thead style='background:#eee;'>
        <tr>
          <th>Posicao</th>
          <th>Area</th>
          <th>Tamanho (m2)</th>
          <th>Tamanho (ha)</th>
          <th>Volume irrigado (L)</th>
          <th>Media (L/ha)</th>
          <th>Media (L/m2)</th>
          <th>L/ha/dia</th>
          <th>Participacao</th>
          <th>Registros</th>
        </tr>
      </thead>
      <tbody>
    ";

    $posicao = 1;
    foreach ($comparativo as $linha) {
        $html .= '<tr>';
        $html .= '<td>' . $posicao . '</td>';
        $html .= '<td>' . htmlspecialchars($linha['nome']) . '</td>';
        $html .= '<td>' . number_format($linha['m2'], 2, ',', '.') . '</td>';
        $html .= '<td>' . number_format($linha['ha'], 4, ',', '.') . '</td>';
        $html .= '<td>' . number_format($linha['volume_l'], 2, ',', '.') . '</td>';
        $html .= '<td><b>' . number_format($linha['lha'], 2, ',', '.') . '</b></td>';
        $html .= '<td>' . number_format($linha['lm2'], 4, ',', '.') . '</td>';
        $html .= '<td>' . number_format($linha['lha_dia'], 2, ',', '.') . '</td>';
        $html .= '<td>' . number_format($linha['participacao'], 1, ',', '.') . '%</td>';
        $html .= '<td>' . (int)$linha['registros'] . '</td>';
        $html .= '</tr>';
        $posicao++;
    }

    $html .= '</tbody></table>';

    $html .= "<h2 style='margin-top:20px;'>Evolucao temporal</h2>";
    $html .= "
    <table border='1' width='100%' cellspacing='0' cellpadding='6'>
      <thead style='background:#eee;'>
        <tr>
          <th>Mes</th>
          <th>Volume total (L)</th>
          <th>Consumo especifico (L/ha)</th>
          <th>Eficiencia diaria (L/ha/dia)</th>
          <th>Registros concluidos</th>
        </tr>
      </thead>
      <tbody>
    ";

    if (empty($evolucaoMensal)) {
        $html .= "<tr><td colspan='5'>Sem dados no periodo selecionado.</td></tr>";
    } else {
        foreach ($evolucaoMensal as $mes => $ev) {
            $diasNoMes = 30;
            try {
                $dtFimMes = new DateTime($mes . '-01');
                $dtFimMes->modify('last day of this month');
                $diasNoMes = (int)$dtFimMes->format('d');
            } catch (Throwable $e) {
                $diasNoMes = 30;
            }
            $lhaMes = $totalAreaHa > 0 ? ($ev['volume_l'] / $totalAreaHa) : 0;
            $lhaDiaMes = $diasNoMes > 0 ? ($lhaMes / $diasNoMes) : 0;

            $html .= '<tr>';
            $html .= '<td>' . date('m/Y', strtotime($mes . '-01')) . '</td>';
            $html .= '<td>' . number_format($ev['volume_l'], 2, ',', '.') . '</td>';
            $html .= '<td>' . number_format($lhaMes, 2, ',', '.') . '</td>';
            $html .= '<td>' . number_format($lhaDiaMes, 2, ',', '.') . '</td>';
            $html .= '<td>' . (int)$ev['registros'] . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 40,
        'margin_bottom' => 18,
        'tempDir' => __DIR__ . '/../../tmp/mpdf'
    ]);

    $logoFrutag = __DIR__ . '/../../img/logo-frutag.png';
    $logoCaderno = __DIR__ . '/../../img/logo-color.png';
    $imgFrutag = file_exists($logoFrutag) ? base64_encode(file_get_contents($logoFrutag)) : '';
    $imgCaderno = file_exists($logoCaderno) ? base64_encode(file_get_contents($logoCaderno)) : '';

    $mpdf->SetHTMLHeader('
    <div style="border-bottom:1px solid #ccc; padding-bottom:5px; font-family:sans-serif;">
      <div style="width:33%; float:left;">
        <img src="data:image/png;base64,' . $imgFrutag . '" width="110">
      </div>
      <div style="width:34%; float:left; text-align:center; font-weight:bold; font-size:16px; color:#1565c0;">
        Relatorio de Irrigacao
      </div>
      <div style="width:33%; float:right; text-align:right;">
        <img src="data:image/png;base64,' . $imgCaderno . '" width="110">
      </div>
      <div style="clear:both;"></div>
    </div>');

    $mpdf->SetHTMLFooter('
    <div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
      Pagina {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
    </div>');

    $mpdf->WriteHTML($html);
    header('Content-Type: application/pdf');
    $mpdf->Output('relatorio_irrigacao.pdf', 'I');
} catch (Throwable $e) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erro ao gerar relatorio: ' . $e->getMessage();
}
