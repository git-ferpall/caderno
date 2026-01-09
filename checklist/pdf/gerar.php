<?php
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

$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');

/* 🔐 Valida checklist */
$stmt = $mysqli->prepare("
    SELECT * FROM checklists
    WHERE id = ? AND user_id = ? AND concluido = 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) die('Checklist não encontrado');

/* 🔐 Hash */
$hash = gerarHashChecklist($mysqli, $checklist_id);

/* 🔎 Itens */
$stmt = $mysqli->prepare("
    SELECT * FROM checklist_itens
    WHERE checklist_id = ?
    ORDER BY ordem
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 🔎 Arquivos */
$stmt = $mysqli->prepare("
    SELECT * FROM checklist_item_arquivos
    WHERE checklist_item_id IN (
        SELECT id FROM checklist_itens WHERE checklist_id = ?
    )
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$arquivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 🔳 QR Code */
$url = "https://caderno.frutag.com.br/checklist/validar.php?hash=$hash";
$qr = QrCode::create($url)->setSize(180);
$writer = new PngWriter();
$qrImg = $writer->write($qr)->getDataUri();

/* 📄 PDF */
$mpdf = new Mpdf();
$html = "<h1>{$checklist['titulo']}</h1>";
$html .= "<p><strong>Hash:</strong> $hash</p>";
$html .= "<img src='$qrImg'><hr>";

foreach ($itens as $i) {
    $html .= "<p><strong>{$i['descricao']}</strong> ";
    $html .= $i['concluido'] ? '✔️' : '❌';
    if ($i['observacao']) {
        $html .= "<br><em>{$i['observacao']}</em>";
    }

    foreach ($arquivos as $a) {
        if ($a['checklist_item_id'] == $i['id']) {
            $path = __DIR__ . "/../../uploads/checklists/$checklist_id/item_{$i['id']}/{$a['arquivo']}";

            if ($a['tipo'] === 'foto') {
                $html .= "<br><img src='$path' style='max-width:300px'>";
            } else {
                $html .= "<br>📄 {$a['arquivo']}";
            }
        }
    }

    $html .= "</p><hr>";
}

$mpdf->WriteHTML($html);
$mpdf->Output("checklist_$checklist_id.pdf", 'I');
