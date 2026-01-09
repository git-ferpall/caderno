<?php
/**
 * Gera PDF do checklist fechado com Hash + QR Code
 * Stack: MySQLi + protect.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../funcoes/gerar_hash.php';

require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendor/phpqrcode/qrlib.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$checklist_id) die('Checklist inválido');

/* 🔎 Checklist (SOMENTE FECHADO) */
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

/* 🔐 Valida integridade */
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

/* 🔳 QR Code */
$tmp_qr = sys_get_temp_dir() . '/qr_' . $checklist_id . '.png';
$url_validar = 'https://caderno.frutag.com.br/checklist/validar/?hash=' . $checklist['hash_documento'];
QRcode::png($url_validar, $tmp_qr, QR_ECLEVEL_M, 4);

/* =========================
 * 📄 PDF
 * ========================= */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

$pdf->Cell(0, 10, 'Checklist Finalizado', 0, 1);
$pdf->Ln(3);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, 'Titulo: ' . $checklist['titulo'], 0, 1);
$pdf->Cell(0, 8, 'Fechado em: ' . $checklist['fechado_em'], 0, 1);
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'Itens', 0, 1);

$pdf->SetFont('Arial', '', 10);

foreach ($itens as $i) {
    $status = $i['concluido'] ? '[X]' : '[ ]';
    $pdf->MultiCell(0, 6, $status . ' ' . $i['descricao']);

    if (!empty($i['observacao'])) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->MultiCell(0, 5, 'Obs: ' . $i['observacao']);
        $pdf->SetFont('Arial', '', 10);
    }
}

/* 🔐 Hash */
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, 'Hash de integridade:', 0, 1);
$pdf->SetFont('Courier', '', 8);
$pdf->MultiCell(0, 5, $checklist['hash_documento']);

/* 🔳 QR Code no rodapé */
$pdf->Image($tmp_qr, 160, 240, 35);

/* 🖨️ Saída */
$pdf->Output('I', 'checklist_' . $checklist_id . '.pdf');

/* 🧹 Limpa */
@unlink($tmp_qr);
exit;
