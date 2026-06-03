<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
if (is_file(__DIR__ . '/mpdf_bootstrap.php')) {
    require_once __DIR__ . '/mpdf_bootstrap.php';
} elseif (!function_exists('cadernoMpdfTempDir')) {
    function cadernoMpdfTempDir(): string
    {
        $base = dirname(__DIR__, 2) . '/tmp/mpdf';
        foreach ([$base, $base . '/mpdf', sys_get_temp_dir() . '/caderno-mpdf', sys_get_temp_dir() . '/caderno-mpdf/mpdf'] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            @chmod($dir, 0777);
        }
        if (is_writable($base)) {
            return $base;
        }
        $fallback = rtrim(sys_get_temp_dir(), '/\\') . '/caderno-mpdf';
        if (is_writable($fallback)) {
            return $fallback;
        }
        throw new RuntimeException('Pasta temporária do PDF sem permissão de escrita.');
    }
}

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

session_start();

function relatorioPdfErro(string $msg, int $code = 500): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'err' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!class_exists(Mpdf::class)) {
    relatorioPdfErro(
        'Biblioteca de PDF (mPDF) não instalada no servidor. Execute: composer require mpdf/mpdf',
        503
    );
}

try {
    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) throw new Exception("Usuário não autenticado.");

    // === Filtros ===
    $propriedades = $_POST['pfpropriedades'] ?? [];
    $cultivo = $_POST['pfcult'] ?? '';
    $area = $_POST['pfarea'] ?? '';
    $manejo = $_POST['pfmane'] ?? '';
    $data_ini = $_POST['pfini'] ?? date('Y-m-01');
    $data_fim = $_POST['pffin'] ?? date('Y-m-t');

    // === Pega todas as propriedades se nada foi selecionado ===
    if (empty($propriedades)) {
        $stmtProp = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ?");
        $stmtProp->bind_param("i", $user_id);
        $stmtProp->execute();
        $resProp = $stmtProp->get_result();
        while ($row = $resProp->fetch_assoc()) $propriedades[] = $row['id'];
        $stmtProp->close();
    }

    if (empty($propriedades)) throw new Exception("Nenhuma propriedade encontrada para este usuário.");

    // === Lista os nomes das propriedades selecionadas ===
    $placeholdersProps = implode(',', array_fill(0, count($propriedades), '?'));
    $typesProps = str_repeat('i', count($propriedades));

    $stmtProps = $mysqli->prepare("SELECT nome_razao FROM propriedades WHERE id IN ($placeholdersProps)");
    $stmtProps->bind_param($typesProps, ...$propriedades);
    $stmtProps->execute();
    $resProps = $stmtProps->get_result();
    $nomes_props = [];
    while ($p = $resProps->fetch_assoc()) $nomes_props[] = $p['nome_razao'];
    $stmtProps->close();

    // === Query principal ===
    $placeholders = implode(',', array_fill(0, count($propriedades), '?'));

    $sql = "
    SELECT 
            a.id, a.tipo, a.data, a.status, a.observacoes, a.data_conclusao,
            a.quantidade, a.unidade,
            ar.nome AS area_nome,
            p.nome AS produto_nome,
            prop.nome_razao AS propriedade_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad_area 
            ON ad_area.apontamento_id = a.id 
            AND ad_area.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad_area.valor
        LEFT JOIN apontamento_detalhes ad_prod 
            ON ad_prod.apontamento_id = a.id 
            AND ad_prod.campo = 'produto_id'
        LEFT JOIN produtos p ON p.id = ad_prod.valor
        LEFT JOIN propriedades prop ON prop.id = a.propriedade_id
        WHERE a.propriedade_id IN ($placeholders)
        AND COALESCE(a.data_conclusao, a.data) BETWEEN ? AND ?
    ";

    $params = [];
    $types  = "";

    // 🔹 Primeiro vêm os IDs (porque estão primeiro na query)
    foreach ($propriedades as $pid) {
        $params[] = $pid;
        $types   .= "i";
    }

    // 🔹 Depois vêm as datas
    $params[] = $data_ini;
    $params[] = $data_fim;
    $types   .= "ss";

    // 🔹 FILTRO CULTIVO
    if (!empty($cultivo)) {
        $sql .= " AND p.nome = ?";
        $params[] = $cultivo;
        $types   .= "s";
    }

    // 🔹 FILTRO ÁREA
    if (!empty($area)) {
        $sql .= " AND ar.nome = ?";
        $params[] = $area;
        $types   .= "s";
    }

    // 🔹 FILTRO MANEJO
    if (!empty($manejo)) {
        $sql .= " AND a.tipo = ?";
        $params[] = $manejo;
        $types   .= "s";
    }

    $sql .= " ORDER BY a.data DESC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $pendentes = [];
    $concluidos = [];
    $atrasados = [];
    $hoje = strtotime(date('Y-m-d'));

    while ($row = $res->fetch_assoc()) {

        $data_base = !empty($row['data_conclusao']) 
            ? $row['data_conclusao'] 
            : $row['data'];

        $data_item = strtotime($data_base);

        if (strtolower($row['status']) === 'concluido') {
            $concluidos[] = $row;
        } else {
            if ($data_item < $hoje) {
                $atrasados[] = $row;
            }
            $pendentes[] = $row;
        }
    }

    $total_pendentes = count($pendentes);
    $total_concluidos = count($concluidos);
    $total_atrasados = count($atrasados);
    $total_geral = $total_pendentes + $total_concluidos;

    $pct_concluidos = $total_geral > 0 ? round(($total_concluidos / $total_geral) * 100) : 0;
    $pct_pendentes  = 100 - $pct_concluidos;
    $pct_atrasados  = $total_pendentes > 0 ? round(($total_atrasados / $total_pendentes) * 100) : 0;
    $pct_emdia      = 100 - $pct_atrasados;

    // === PDF ===
    $tempDir = cadernoMpdfTempDir();

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 45,
        'margin_bottom' => 20,
        'tempDir' => $tempDir,
        'allowRemoteImages' => false,
    ]);

    $logo_frutag = __DIR__ . '/../../img/logo-frutag.png';
    $logo_caderno = __DIR__ . '/../../img/logo-color.png';

    $img_frutag = file_exists($logo_frutag) ? base64_encode(file_get_contents($logo_frutag)) : '';
    $img_caderno = file_exists($logo_caderno) ? base64_encode(file_get_contents($logo_caderno)) : '';

    $mpdf->SetHTMLHeader('
        <div style="border-bottom:1px solid #ccc; padding-bottom:5px;">
            <img src="data:image/png;base64,' . $img_frutag . '" width="120" style="float:left;">
            <img src="data:image/png;base64,' . $img_caderno . '" width="120" style="float:right;">
            <div style="text-align:center; font-weight:bold; font-size:16px; color:#2e7d32;">Relatório de Manejos - Caderno de Campo</div>
        </div>
    ');

    $mpdf->SetHTMLFooter('
        <div style="border-top:1px solid #ccc; text-align:center; font-size:10px; color:#777; padding-top:4px;">
            Página {PAGENO} de {nb} | Gerado em ' . date('d/m/Y H:i') . '
        </div>
    ');

    // === Estilo + gráficos ===
    $html = '
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #2e7d32; margin-bottom: 5px; }
        h2 { text-align: left; color: #555; border-bottom:1px solid #ccc; margin-top:25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #4caf50; color: white; }
        .atrasado { background-color: #ffebee; color: #c62828; font-weight:bold; }
        .resumo { margin:10px 0 20px 0; font-size:13px; }
        .graficos { text-align:center; margin:20px 0; }
        .grafico-img { width:45%; display:inline-block; margin:0 2%; }
    </style>
    ';

    $html .= '<h1>Relatório de Manejos</h1>';
    $html .= '<div class="resumo">
        <strong>Propriedades:</strong> ' . implode(', ', $nomes_props) . '<br>
        <strong>Período:</strong> ' . date('d/m/Y', strtotime($data_ini)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '<br>
        <strong>Total:</strong> ' . $total_geral . ' | 
        <strong>Concluídos:</strong> ' . $total_concluidos . ' | 
        <strong>Pendentes:</strong> ' . $total_pendentes . ' | 
        <strong>Atrasados:</strong> ' . $total_atrasados . '
    </div>';

    // === Gráficos em HTML (sem dependência externa — funciona no Docker) ===
    function gerarGraficoHtml(string $titulo, array $labels, array $data, array $cores): string
    {
        $html = '<div class="grafico-box" style="display:inline-block;width:46%;margin:1%;vertical-align:top;text-align:left;">';
        $html .= '<div style="font-weight:bold;text-align:center;margin-bottom:8px;">' . htmlspecialchars($titulo) . '</div>';
        foreach ($labels as $i => $label) {
            $pct = max(0, min(100, (int) ($data[$i] ?? 0)));
            $cor = $cores[$i] ?? '#999';
            $html .= '<div style="margin:6px 0;font-size:11px;">';
            $html .= '<span style="display:inline-block;width:10px;height:10px;background:' . $cor . ';margin-right:6px;border-radius:2px;"></span>';
            $html .= htmlspecialchars($label) . ': <strong>' . $pct . '%</strong>';
            $html .= '<div style="background:#eee;border-radius:4px;height:10px;margin-top:3px;overflow:hidden;">';
            $html .= '<div style="width:' . $pct . '%;background:' . $cor . ';height:10px;"></div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }

    $html .= '<div class="graficos" style="text-align:center;margin:16px 0;">'
        . gerarGraficoHtml('Concluídos x Pendentes', ['Concluídos', 'Pendentes'], [$pct_concluidos, $pct_pendentes], ['#4caf50', '#ff9800'])
        . gerarGraficoHtml('Em dia x Atrasados', ['Em dia', 'Atrasados'], [$pct_emdia, $pct_atrasados], ['#4caf50', '#e53935'])
        . '</div>';

    // === Monta as tabelas ===
    function formatarQuantidadeColhida(array $d): string
    {
        if (strtolower($d['tipo'] ?? '') !== 'colheita') {
            return '—';
        }

        $qtd = $d['quantidade'] ?? null;
        if ($qtd === null || $qtd === '' || (float)$qtd <= 0) {
            return '—';
        }

        $unidade = trim((string)($d['unidade'] ?? ''));
        $qtdFmt = number_format((float)$qtd, 2, ',', '.');

        return $unidade !== '' ? ($qtdFmt . ' ' . $unidade) : $qtdFmt;
    }

    function montarTabela($titulo, $dados, $classe = '') {
        if (empty($dados)) return '';

        $html = '<h2>' . $titulo . '</h2>
        <table>
            <thead>
                <tr>
                    <th>Data Prevista</th>
                    <th>Data Conclusão</th>
                    <th>Propriedade</th>
                    <th>Área</th>
                    <th>Produto</th>
                    <th>Qtd. Colhida</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($dados as $d) {

            // 🔹 Define qual data mostrar
            $dataExibida = (
                strtolower($d['status']) === 'concluido'
                && !empty($d['data_conclusao'])
            )
            ? $d['data_conclusao']
            : $d['data'];

            // 🔹 Define classe de atraso (apenas para não concluídos)
            $extra = (
                $classe 
                && strtotime($d['data']) < strtotime(date('Y-m-d')) 
                && $d['status'] !== 'concluido'
            )
            ? ' class="' . $classe . '"'
            : '';

            $html .= '<tr' . $extra . '>
                <td>' . (!empty($d['data']) ? date('d/m/Y', strtotime($d['data'])) : '—') . '</td>
                <td>' . (!empty($d['data_conclusao']) ? date('d/m/Y', strtotime($d['data_conclusao'])) : '—') . '</td>
                <td>' . htmlspecialchars($d['propriedade_nome'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['area_nome'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['produto_nome'] ?? '—') . '</td>
                <td>' . formatarQuantidadeColhida($d) . '</td>
                <td>' . ucfirst($d['tipo'] ?? '—') . '</td>
                <td>' . ucfirst($d['status'] ?? '—') . '</td>
                <td>' . htmlspecialchars($d['observacoes'] ?? '—') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    $html .= montarTabela("Manejos Concluídos", $concluidos);
    $html .= montarTabela("Manejos Pendentes", $pendentes);
    if (!empty($atrasados)) $html .= montarTabela("⚠ Pendências Atrasadas", $atrasados, 'atrasado');

    $mpdf->WriteHTML($html);
    header('Content-Type: application/pdf');
    $mpdf->Output('relatorio_manejos.pdf', Destination::INLINE);

} catch (Throwable $e) {
    relatorioPdfErro($e->getMessage());
}
