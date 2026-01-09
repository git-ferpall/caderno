<?php
/**
 * Gera PDF do checklist fechado
 * Stack: MySQLi + MPDF + endroid/qr-code v3
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

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

/* 🔎 Checklist (somente fechado) */
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
    die('Checklist não encontrado, não finalizado ou sem permissão');
}

/* 🔐 Validação de integridade */
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

/* 🔳 QR CODE (endroid v3) */
$validarUrl = 'https://caderno.frutag.com.br/checklist/validar/?hash=' . $checklist['hash_documento'];

$qrCode = new QrCode($validarUrl);
$qrCode->setSize(180);
$qrCode->setMargin(5);

$writer = new PngWriter();
$result = $writer->write($qrCode);
$qrBase64 = $result->getDataUri();

/* =========================
 * 🧾 HTML DO PDF
 * ========================= */
$html = '
<style>
body { font-family: sans-serif; font-size: 12px }
h1 { font-size: 18px; margin-bottom: 10px }
h3 { margin-top: 20px }
ul { padding-left: 15px }
li { margin-bottom: 6px }
.item-ok { color: #0a7a0a }
.item-no { color: #999 }
.hash {
    font-family: monospace;
    font-size: 9px;
    word-break: break-all;
    background: #f5f5f5;
    padding: 6px;
}
.footer {
    margin-top: 25px;
    border-top: 1px solid #ccc;
    padding-top: 10px;
    text-align: center;
}
.small { font-size: 10px; color: #555 }
</style>

<h1>Checklist Finalizado</h1>

<p><strong>Título:</strong> ' . htmlspecialchars($checklist['titulo']) . '</p>
<p><strong>Fechado em:</strong> ' . htmlspecialchars($checklist['fechado_em']) . '</p>

<h3>Itens</h3>
<ul>
';

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

    <p class="small">
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
