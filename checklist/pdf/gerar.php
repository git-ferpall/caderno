<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once '../../vendor/autoload.php';
require_once '../qr.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$checklist_id = $_GET['id'];

/* ===============================
   1️⃣ Buscar checklist fechado
   =============================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM checklists
    WHERE id = ? AND hash_documento IS NOT NULL
");
$stmt->execute([$checklist_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$checklist) {
    die('Checklist não encontrado ou não fechado');
}

/* ===============================
   2️⃣ Buscar itens + anexos
   =============================== */
$sql = "
SELECT 
    i.id,
    i.descricao,
    i.concluido,
    i.observacao,
    i.data_conclusao,
    a.tipo,
    a.arquivo
FROM checklist_itens i
LEFT JOIN checklist_item_anexos a
    ON a.checklist_item_id = i.id
WHERE i.checklist_id = ?
ORDER BY i.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$checklist_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   3️⃣ Organizar estrutura
   =============================== */
$itens = [];

foreach ($rows as $r) {
    if (!isset($itens[$r['id']])) {
        $itens[$r['id']] = [
            'descricao' => $r['descricao'],
            'concluido' => $r['concluido'],
            'observacao'=> $r['observacao'],
            'data'      => $r['data_conclusao'],
            'fotos'     => [],
            'docs'      => []
        ];
    }

    if ($r['arquivo']) {
        if ($r['tipo'] === 'foto') {
            $itens[$r['id']]['fotos'][] = $r['arquivo'];
        } else {
            $itens[$r['id']]['docs'][] = $r['arquivo'];
        }
    }
}

/* ===============================
   4️⃣ QR Code
   =============================== */
$qrFile = gerarQRCodeChecklist($checklist['hash_documento']);

/* ===============================
   5️⃣ HTML do PDF
   =============================== */
ob_start();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans; font-size: 12px; }
h1 { font-size: 20px; margin-bottom: 5px; }
hr { margin: 10px 0; }
.item { margin-bottom: 15px; }
.status-ok { color: green; font-weight: bold; }
.status-no { color: red; font-weight: bold; }
.foto { width: 120px; margin: 4px; border: 1px solid #ccc; }
.footer { font-size: 9px; color: #555; }
</style>
</head>

<body>

<h1><?= htmlspecialchars($checklist['titulo']) ?></h1>
<p>
<strong>Checklist ID:</strong> <?= $checklist['id'] ?><br>
<strong>Fechado em:</strong> <?= date('d/m/Y H:i', strtotime($checklist['fechado_em'])) ?>
</p>

<hr>

<?php foreach ($itens as $item): ?>
<div class="item">
  <strong><?= htmlspecialchars($item['descricao']) ?></strong><br>

  Status:
  <?php if ($item['concluido']): ?>
    <span class="status-ok">✔ Concluído</span>
  <?php else: ?>
    <span class="status-no">✖ Não concluído</span>
  <?php endif; ?>

  <?php if ($item['observacao']): ?>
    <p><strong>Observação:</strong><br><?= nl2br(htmlspecialchars($item['observacao'])) ?></p>
  <?php endif; ?>

  <?php if ($item['fotos']): ?>
    <div>
      <?php foreach ($item['fotos'] as $f): ?>
        <img class="foto"
            src="<?= realpath(
                __DIR__."/../../uploads/checklist/{$checklist_id}/{$item_id}/{$f}"
            ) ?>">
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($item['docs']): ?>
    <p><strong>Documentos:</strong></p>
    <ul>
      <?php foreach ($item['docs'] as $d): ?>
        <li><?= htmlspecialchars($d) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

</div>
<?php endforeach; ?>

<hr>

<table width="100%">
<tr>
  <td width="70%" class="footer">
    Documento gerado automaticamente<br>
    Hash SHA-256:<br>
    <strong><?= $checklist['hash_documento'] ?></strong>
  </td>
  <td width="30%" align="right">
    <img src="<?= $qrFile ?>" width="100">
  </td>
</tr>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

/* ===============================
   6️⃣ Gerar PDF
   =============================== */
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream(
    "checklist_{$checklist_id}.pdf",
    ['Attachment' => false]
);
