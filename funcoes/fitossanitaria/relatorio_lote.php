<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/../apontamento_arquivos.php';
require_once __DIR__ . '/score.php';
require_once __DIR__ . '/lote.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../relatorios/mpdf_bootstrap.php';

use Mpdf\Mpdf;

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int) ($payload['sub'] ?? 0);
}

if (!$user_id) {
    http_response_code(401);
    die('Não autenticado');
}

$area_id = (int) ($_POST['area_id'] ?? $_GET['area_id'] ?? 0);
if ($area_id <= 0) {
    http_response_code(400);
    die('Informe area_id');
}

$prop = obterPropriedadeAtiva($mysqli, $user_id);
if (!$prop) {
    http_response_code(400);
    die('Nenhuma propriedade ativa');
}

$painel = fsMontarPainelArea($mysqli, $user_id, (int) $prop['id'], $area_id);
if (empty($painel['ok'])) {
    http_response_code(404);
    die($painel['msg'] ?? 'Área não encontrada');
}

$lote = $painel['lote'] ?? null;
$score = $painel['score'] ?? [];
$area = $painel['area'] ?? [];
$clima = $painel['clima'] ?? [];
$csfi = $painel['csfi'] ?? [];

$html = '<html><head><meta charset="utf-8"><style>
body{font-family:sans-serif;font-size:11pt;color:#222}
h1{font-size:16pt;color:#2e6b30;margin:0 0 8px}
h2{font-size:12pt;color:#444;border-bottom:1px solid #ddd;padding-bottom:4px;margin-top:18px}
.meta{color:#666;font-size:9pt}
.box{background:#f6f9f7;padding:10px;border-radius:6px;margin:8px 0}
.hash{font-family:monospace;font-size:8pt;word-break:break-all}
.alert{color:#c62828}
</style></head><body>';

$html .= '<h1>Relatório técnico — Lote Fitossanitário</h1>';
$html .= '<p class="meta">Gerado em ' . date('d/m/Y H:i') . ' · Caderno de Campo Frutag</p>';

$html .= '<div class="box">';
$html .= '<strong>Propriedade:</strong> ' . htmlspecialchars((string) ($prop['nome_razao'] ?? '')) . '<br>';
$html .= '<strong>Área:</strong> ' . htmlspecialchars((string) ($area['nome'] ?? '')) . '<br>';
if ($lote) {
    $html .= '<strong>Lote Frutag:</strong> ' . htmlspecialchars((string) $lote['codigo_lote']) . '<br>';
    $html .= '<strong>Status:</strong> ' . htmlspecialchars((string) ($lote['status_label'] ?? '')) . '<br>';
}
$html .= '<strong>Score:</strong> ' . htmlspecialchars((string) ($score['label'] ?? '')) . ' (' . htmlspecialchars((string) ($score['nivel'] ?? '')) . ')<br>';
$html .= '<strong>Data referência:</strong> ' . htmlspecialchars((string) ($painel['data_referencia'] ?? ''));
$html .= '</div>';

$html .= '<h2>Diagnóstico</h2><p>' . htmlspecialchars((string) ($painel['diagnostico'] ?? '')) . '</p>';
$html .= '<h2>Carência e resíduo</h2><p>' . htmlspecialchars((string) ($painel['risco_residuo']['resumo'] ?? '')) . '</p>';

$html .= '<h2>Clima (aplicação)</h2><p>' . htmlspecialchars((string) ($clima['resumo'] ?? '')) . '</p>';
if (!empty($clima['alertas'])) {
    $html .= '<ul class="alert">';
    foreach ($clima['alertas'] as $a) {
        $html .= '<li>' . htmlspecialchars((string) $a) . '</li>';
    }
    $html .= '</ul>';
}

$html .= '<h2>CSFI</h2><p>' . htmlspecialchars((string) ($csfi['resumo'] ?? '')) . '</p>';
$html .= '<h2>Recomendação IA</h2><p>' . htmlspecialchars((string) ($painel['recomendacao'] ?? '')) . '</p>';
$html .= '<h2>Ação sugerida</h2><p>' . htmlspecialchars((string) ($painel['acao_sugerida'] ?? '')) . '</p>';

if (!empty($painel['historico'])) {
    $html .= '<h2>Histórico de aplicações</h2><table width="100%" cellpadding="4" cellspacing="0" border="1" style="border-collapse:collapse;font-size:9pt">';
    $html .= '<tr style="background:#eee"><th>Data</th><th>Produto</th><th>Tipo</th><th>Carência</th></tr>';
    foreach (array_slice($painel['historico'], 0, 15) as $h) {
        $html .= '<tr><td>' . htmlspecialchars((string) ($h['data_aplicacao'] ?? '')) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) ($h['produto'] ?? '')) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) ($h['tipo'] ?? '')) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) ($h['carencia_dias'] ?? '—')) . ' d</td></tr>';
    }
    $html .= '</table>';
}

if ($lote) {
    $html .= '<h2>Auditoria</h2>';
    $html .= '<p class="hash"><strong>Hash SHA-256:</strong><br>' . htmlspecialchars((string) $lote['hash_auditoria']) . '</p>';
    $html .= '<p class="meta">Verificação: ' . htmlspecialchars((string) ($lote['url_verificacao'] ?? '')) . '</p>';
    if (!empty($lote['url_qrcode'])) {
        $html .= '<p style="text-align:center"><img src="' . htmlspecialchars((string) $lote['url_qrcode']) . '" width="120" alt="QR Code" /></p>';
    }
}

$html .= '<p class="meta" style="margin-top:24px">' . htmlspecialchars((string) ($painel['aviso_legal'] ?? '')) . '</p>';
$html .= '</body></html>';

try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => cadernoMpdfTempDir(),
    ]);
    $mpdf->WriteHTML($html);
    $nome = 'lote-fitossanitario-' . ($lote['codigo_lote'] ?? $area_id) . '.pdf';
    $mpdf->Output($nome, 'D');
} catch (Throwable $e) {
    http_response_code(500);
    die('Erro ao gerar PDF: ' . $e->getMessage());
}
