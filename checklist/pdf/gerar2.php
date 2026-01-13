<?php
/**
 * Geração de PDF do checklist FINALIZADO (PÚBLICO)
 * - Acesso via HASH (sem login)
 * - Itens preenchidos
 * - Observações
 * - Fotos e documentos
 * - Assinatura digital
 * - Hash de integridade
 * - QR Code de validação
 * - Data/hora local e UTC
 * - Carimbo de documento validado
 * - Numeração de páginas
 */

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/* 🔑 HASH público */
$hash = $_GET['hash'] ?? '';
if (!$hash || strlen($hash) !== 64) {
    http_response_code(403);
    exit('Hash inválido');
}

/* 🔎 Checklist (somente finalizado) */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklists
    WHERE hash_documento = ?
      AND concluido = 1
    LIMIT 1
");
$stmt->bind_param("s", $hash);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    http_response_code(404);
    exit('Checklist não encontrado ou não finalizado');
}

$checklist_id = (int)$checklist['id'];

/* 🔐 Revalidação criptográfica */
$hash_atual = gerarHashChecklist($mysqli, $checklist_id);
if (!hash_equals($checklist['hash_documento'], $hash_atual)) {
    http_response_code(409);
    exit('Documento inválido ou adulterado');
}

/* 🖋 Responsável (sem login – LGPD safe) */
$responsavel = 'Responsável registrado no sistema';

/* 🌐 IP */
$ip_usuario = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? 'IP não identificado';

/* 🕒 Datas */
$dataHoraLocal = date('d/m/Y H:i:s');
$dataHoraUTC   = gmdate('d/m/Y H:i:s');

/* 🔎 Itens */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklist_itens
    WHERE checklist_id = ?
    ORDER BY ordem
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 🔎 Arquivos */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklist_item_arquivos
    WHERE checklist_item_id IN (
        SELECT id FROM checklist_itens WHERE checklist_id = ?
    )
    ORDER BY checklist_item_id, criado_em
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$arquivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ✍️ Assinatura */
$assinaturaPath = __DIR__ . "/../../uploads/checklists/$checklist_id/assinatura.png";
$temAssinatura  = file_exists($assinaturaPath);

/* 🔳 QR Code */
$urlValidacao = "https://caderno.frutag.com.br/v/$hash";
$qrCode = new QrCode($urlValidacao);
$writer = new PngWriter();
$qrImg  = $writer->write($qrCode)->getDataUri();

/* 📄 PDF */
$mpdf = new Mpdf([
    'tempDir'       => __DIR__ . '/../../tmp/mpdf',
    'margin_top'    => 5,
    'margin_bottom' => 15,
    'margin_left'   => 15,
    'margin_right'  => 15
]);

$mpdf->SetFooter('{PAGENO} / {nbpg}');

/* 🎨 CSS */
$css = "
body { font-family: Arial; font-size: 12px; color:#333; }

.header {
    text-align:center;
    margin-bottom:20px;
}

.header img {
    height:80px;
    margin-bottom:8px;
}

.meta {
    font-size:11px;
    color:#555;
}

.carimbo {
    position:absolute;
    top:120px;
    right:-30px;
    transform:rotate(-25deg);
    border:3px solid #4CAF50;
    color:#4CAF50;
    font-size:18px;
    font-weight:bold;
    padding:8px 16px;
}

.section {
    font-size:16px;
    border-bottom:2px solid #4CAF50;
    margin:25px 0 10px;
}

.item {
    border:1px solid #ddd;
    border-radius:6px;
    padding:10px;
    margin-bottom:10px;
}

.item-header {
    display:flex;
    justify-content:space-between;
    font-weight:bold;
}

.ok { color:#2e7d32; }
.no { color:#c62828; }

.obs {
    margin-top:6px;
    font-style:italic;
    color:#555;
}

.item img {
    margin-top:6px;
    max-width:260px;
}

.hash {
    font-size:9px;
    word-break:break-all;
}

.assinatura-qrcode {
    width:100%;
    margin-top:30px;
}

.assinatura-qrcode td {
    text-align:center;
    vertical-align:middle;
}

.assinatura-qrcode img {
    max-width:220px;
}

.footer {
    text-align:center;
    font-size:10px;
    color:#666;
    margin-top:25px;
}
";

$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

/* 🧾 HTML */
$logo = __DIR__ . "/../../img/logo-color.png";

$html = "
<div class='header'>
    <img src='$logo' style='height:80px;width:auto;display:block;margin:0 auto 8px;'>
    <h2>{$checklist['titulo']}</h2>
    <div class='meta'>
        Checklist #{$checklist_id}<br>
        Fechado em {$checklist['fechado_em']}<br>
        Gerado em $dataHoraLocal (UTC $dataHoraUTC)
    </div>
</div>

<div class='carimbo'>DOCUMENTO VALIDADO</div>

<p class='hash'><strong>Hash de integridade:</strong><br>$hash</p>

<div class='section'>Itens do checklist</div>
";

/* 📋 ITENS */
foreach ($itens as $i) {

    $statusClass = $i['concluido'] ? 'ok' : 'no';
    $statusTexto = $i['concluido'] ? '✔ OK' : '✖ Não';

    $html .= "
    <div class='item'>
        <div class='item-header'>
            <span>{$i['descricao']}</span>
            <span class='$statusClass'>$statusTexto</span>
        </div>
    ";

    if (!empty($i['observacao'])) {
        $html .= "<div class='obs'>Obs: {$i['observacao']}</div>";
    }

    foreach ($arquivos as $a) {
        if ($a['checklist_item_id'] != $i['id']) continue;

        $path = __DIR__ . "/../../uploads/checklists/$checklist_id/item_{$i['id']}/{$a['arquivo']}";
        if (!file_exists($path)) continue;

        if ($a['tipo'] === 'foto') {
            $html .= "<div><img src='$path'></div>";
        } else {
            $html .= "<div>📄 Documento: {$a['arquivo']}</div>";
        }
    }

    $html .= "</div>";
}

/* ✍️ ASSINATURA + QR */
if ($temAssinatura) {
    $html .= "
    <div class='section'>Validação</div>
    <table class='assinatura-qrcode'>
        <tr>
            <td width='50%'>
                <strong>Assinatura digital</strong><br><br>
                <img src='$assinaturaPath'><br><br>
                <small>Assinado em {$checklist['fechado_em']}</small>
            </td>
            <td width='50%'>
                <strong>QR Code de validação</strong><br><br>
                <img src='$qrImg'><br>
                <small>$urlValidacao</small>
            </td>
        </tr>
    </table>
    ";
}

$html .= "
<div class='footer'>
Documento validado por hash criptográfico.<br>
Data/hora local: $dataHoraLocal | UTC: $dataHoraUTC
</div>
";

$mpdf->WriteHTML($html);
$mpdf->Output("checklist_$checklist_id.pdf", 'I');
