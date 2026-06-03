<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/relatorio_manejos_helpers.php';
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

    $user_id = relatorioManejosUserId();
    $dados = relatorioManejosCarregar($mysqli, $user_id, $_POST);

    $nomes_props = $dados['nomes_props'];
    $concluidos = $dados['concluidos'];
    $pendentes = $dados['pendentes'];
    $atrasados = $dados['atrasados'];
    $total_pendentes = $dados['total_pendentes'];
    $total_concluidos = $dados['total_concluidos'];
    $total_atrasados = $dados['total_atrasados'];
    $total_geral = $dados['total_geral'];
    $pct_concluidos = $dados['pct_concluidos'];
    $pct_pendentes = $dados['pct_pendentes'];
    $pct_atrasados = $dados['pct_atrasados'];
    $pct_emdia = $dados['pct_emdia'];
    $data_ini = $dados['data_ini'];
    $data_fim = $dados['data_fim'];
    $resumo_areas = $dados['resumo_areas'];
    $resumo_por_area = $dados['resumo_por_area'];

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

    if ($resumo_areas) {
        $html .= relatorioManejosHtmlResumoAreas($resumo_por_area);
    }

    $mpdf->WriteHTML($html);
    $paginasTotais = (int) $mpdf->page;
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="relatorio_manejos.pdf"');
    header('X-Pdf-Pages: ' . $paginasTotais);
    header('X-Pdf-Records: ' . (int) $total_geral);
    $mpdf->Output('relatorio_manejos.pdf', Destination::INLINE);

} catch (Throwable $e) {
    relatorioPdfErro($e->getMessage());
}
