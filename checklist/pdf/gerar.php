<?php
/**
 * Gera PDF do checklist fechado
 * Stack: MySQLi + MPDF + QR Code
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

use Mpdf\Mpdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');

/* 🔎 Checklist */
$stmt = $mysqli->prepare("
    SELECT id, titulo, fechado_em, hash_documento
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

/* 🔐 Validação */
$hash_atual = gerarHashChecklist($mysqli, $checklist_id);
if (!hash_equals($checklist['hash_documento'], $hash_atual)) {
    die('Checklist adulterado — PDF bloqueado');
}

/* 🔎 Itens */
$stmt = $mysqli->prepare("
    SELECT descricao, concluido, observacao
    FROM checklist_itens
    WHERE checklist_id = ?
    ORDER BY ordem
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 🔳 QR CODE (base64) */
$validarUrl = 'https://caderno.frutag.com.br/checklist/validar/?hash=' . $checklist['hash_documento'];

$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($validarUrl)
    ->size(180)
    ->margin(5)
    ->build();

$qrBase64 = $qr->getDataUri();

/* =========================
 * 🧾 HTML DO PDF
 * ========================= */
$html = '
<style>
body { font-family: sans-serif; font-size: 12px }
h1 { font-size: 18px }
.item-ok { color: green }
.item-no { color: #999 }
.hash { font-family: monospace; font-size: 9px; word-break: break-all }
.footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px }
</style>

<h1>Checklist Finalizado</h1>

<p><strong>Título:</strong> ' . htmlspecialchars($checklist['titulo']) . '</p>
<p><strong>Fechado em:</strong> ' . $checklist['fechado_em'] . '</p>

<h3>Itens</h3>
<ul>';

foreach ($itens as $i) {
    $html .= '<li class="' . ($i['concluido'] ? 'item-ok' : 'item-no') . '">
        [' . ($i['concluido'] ? 'X' : ' ') . '] ' . htmlspecialchars($i['descricao']);

    if (!empty($i['observacao'])) {
        $html .= '<br><em>Obs:</em> ' . htmlspecialchars($i['observacao']);
    }

    $html .= '</li>';
}

$html .= '
</ul>

<div class="footer">
    <p><strong>Hash de integridade</strong></p>
    <div class="hash">' . $checklist['hash_documento'] . '</div>
    <br>
    <img src="' . $qrBase64 . '" width="120">
    <p style="font-size:10px">
        Valide este documento em:<br>
        ' . $validarUrl . '
    </p>
</div>
';

/* =========================
 * 📄 GERAR PDF
 * ========================= */
$mpdf = new Mpdf([
    'tempDir' => __DIR__ . '/../../tmp'
]);

$mpdf->WriteHTML($html);
$mpdf->Output('checklist_' . $checklist_id . '.pdf', 'I');
exit;
