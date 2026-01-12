<?php
/**
 * Geração de PDF do checklist FINALIZADO
 * - Itens preenchidos
 * - Observações
 * - Fotos e documentos
 * - Assinatura digital
 * - Hash de integridade
 * - QR Code de validação
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Mpdf\Mpdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔐 Checklist finalizado */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklists
    WHERE id = ? AND user_id = ? AND concluido = 1
    LIMIT 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die('Checklist não encontrado ou não finalizado');
}

/* 🔐 Hash */
$hash = $checklist['hash_documento'];
if (!$hash) {
    $hash = gerarHashChecklist($mysqli, $checklist_id);
}

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
$url = "https://caderno.frutag.com.br/checklist/validar.php?hash=$hash";
$qrCode = new QrCode($url);
$writer = new PngWriter();
$result = $writer->write($qrCode);
$qrImg = $result->getDataUri();

/* 📄 PDF */
$mpdf = new Mpdf([
    'tempDir'       => __DIR__ . '/../../tmp/mpdf',
    'margin_top'    => 20,
    'margin_bottom' => 20,
    'margin_left'   => 15,
    'margin_right'  => 15
]);

/* 🎨 ESTILO */
$css = "
body { font-family: sans-serif; font-size: 12px; }
h1 { font-size: 22px; margin-bottom: 10px; }
h2 { font-size: 16px; margin-top: 25px; }
.item { margin-bottom: 12px; }
.status { float:right; font-weight:bold; }
.obs { margin-top:5px; font-style:italic; color:#444; }
hr { border:0; border-top:1px solid #ccc; margin:15px 0; }
.footer { text-align:center; margin-top:40px; font-size:10px; color:#555; }
";

$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

/* 🧾 CABEÇALHO */
$html = "
<h1>{$checklist['titulo']}</h1>

<p>
<strong>ID:</strong> {$checklist['id']}<br>
<strong>Data de fechamento:</strong> {$checklist['fechado_em']}<br>
<strong>Hash de integridade:</strong><br>
<small style='word-break:break-all'>$hash</small>
</p>

<hr>
<h2>Itens do checklist</h2>
";

/* 📋 ITENS */
foreach ($itens as $i) {

    $status = $i['concluido'] ? '✔ OK' : '✖ Não';

    $html .= "
    <div class='item'>
        <strong>{$i['descricao']}</strong>
        <span class='status'>$status</span>
    ";

    if (!empty($i['observacao'])) {
        $html .= "<div class='obs'>Obs: {$i['observacao']}</div>";
    }

    /* Arquivos do item */
    foreach ($arquivos as $a) {

        if ($a['checklist_item_id'] != $i['id']) continue;

        $path = __DIR__ . "/../../uploads/checklists/$checklist_id/item_{$i['id']}/{$a['arquivo']}";
        if (!file_exists($path)) continue;

        if ($a['tipo'] === 'foto') {
            $html .= "
            <div style='margin-top:6px'>
                <img src='$path' style='max-width:280px;border:1px solid #ccc;padding:4px'>
            </div>
            ";
        } else {
            $html .= "
            <div style='margin-top:6px'>
                📄 Documento: {$a['arquivo']}
            </div>
            ";
        }
    }

    $html .= "</div><hr>";
}

/* ✍️ ASSINATURA */
if ($temAssinatura) {
    $html .= "
    <h2>Assinatura</h2>

    <div style='margin-top:10px'>
        <img src='$assinaturaPath' style='width:320px;border:1px solid #000;padding:6px'>
    </div>

    <p style='font-size:11px;color:#555'>
        Assinado digitalmente em {$checklist['fechado_em']}
    </p>

    <hr>
    ";
}

/* 🔳 QR + RODAPÉ */
$html .= "
<div class='footer'>
    <p>Valide este checklist escaneando o QR Code:</p>
    <img src='$qrImg' style='width:140px'><br>
    <small>$url</small>
</div>
";

$mpdf->WriteHTML($html);
$mpdf->Output("checklist_$checklist_id.pdf", 'I');
