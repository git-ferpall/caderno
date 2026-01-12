<?php
/**
 * Geração de PDF do checklist
 * - Fotos embutidas
 * - Documentos listados
 * - Hash de integridade
 * - QR Code (compatível com endroid/qr-code antigo)
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

/* 🔐 Valida checklist finalizado */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklists
    WHERE id = ? AND user_id = ? AND concluido = 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die('Checklist não encontrado ou não finalizado');
}

/* 🔐 Hash (já deve existir; se não, gera) */
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

/* 🔳 QR Code — API ANTIGA (sem create(), sem setSize()) */
$url = "https://caderno.frutag.com.br/checklist/validar.php?hash=$hash";

$qrCode = new QrCode($url);
$writer = new PngWriter();

/*
 * Em versões antigas, o tamanho NÃO é definido no QrCode.
 * O mPDF ajusta o tamanho via HTML.
 */
$result = $writer->write($qrCode);
$qrImg = $result->getDataUri();

/* 📄 PDF */
$mpdf = new Mpdf([
    'margin_top'    => 15,
    'margin_bottom' => 15,
    'margin_left'   => 15,
    'margin_right'  => 15
]);

$html = "
<h1>{$checklist['titulo']}</h1>

<p>
<strong>Checklist ID:</strong> {$checklist['id']}<br>
<strong>Data de fechamento:</strong> {$checklist['fechado_em']}<br>
<strong>Hash de integridade:</strong><br>
<small style='word-break:break-all'>$hash</small>
</p>

<img src='$qrImg' style='width:180px; margin-bottom:20px'>
<hr>
";

foreach ($itens as $i) {

    $html .= "
    <div style='margin-bottom:15px'>
        <strong>{$i['descricao']}</strong>
        <span style='float:right'>" . ($i['concluido'] ? '✔️' : '❌') . "</span>
    ";

    if (!empty($i['observacao'])) {
        $html .= "<br><em>Obs:</em> {$i['observacao']}";
    }

    /* Arquivos do item */
    foreach ($arquivos as $a) {
        if ($a['checklist_item_id'] != $i['id']) continue;

        $path = __DIR__ . "/../../uploads/checklists/$checklist_id/item_{$i['id']}/{$a['arquivo']}";

        if (!file_exists($path)) continue;

        if ($a['tipo'] === 'foto') {
            $html .= "
            <div style='margin-top:8px'>
                <img src='$path' style='max-width:300px;border:1px solid #ccc;padding:4px'>
            </div>
            ";
        } else {
            $html .= "
            <div style='margin-top:8px'>
                📄 Documento: {$a['arquivo']}
            </div>
            ";
        }
    }

    $html .= "</div><hr>";
}

$mpdf->WriteHTML($html);
$mpdf->Output("checklist_$checklist_id.pdf", 'I');
