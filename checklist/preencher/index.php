<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
session_start();

$checklist_id = (int)($_GET['id'] ?? 0);

// 🔎 Checklist
$stmt = $pdo->prepare("SELECT * FROM checklists WHERE id = ?");
$stmt->execute([$checklist_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$checklist) {
    die('Checklist não encontrado');
}

$bloqueado = !empty($checklist['hash_documento']);

// 🔎 Itens
$stmt = $pdo->prepare("
    SELECT *
    FROM checklist_itens
    WHERE checklist_id = ?
    ORDER BY id ASC
");
$stmt->execute([$checklist_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3 class="mb-3"><?= htmlspecialchars($checklist['titulo']) ?></h3>

<?php if ($bloqueado): ?>
<div class="alert alert-warning">
    🔒 Checklist fechado — somente visualização
</div>
<?php endif; ?>

<?php foreach ($itens as $item): ?>

<?php
// anexos do item
$anexosStmt = $pdo->prepare("
    SELECT * FROM checklist_item_anexos
    WHERE checklist_item_id = ?
");
$anexosStmt->execute([$item['id']]);
$anexos = $anexosStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card mb-3">
<div class="card-body">

<!-- ✔ Pergunta -->
<div class="form-check">
  <input class="form-check-input check-item"
         type="checkbox"
         data-id="<?= $item['id'] ?>"
         <?= $item['concluido'] ? 'checked' : '' ?>
         <?= $bloqueado ? 'disabled' : '' ?>>

  <label class="form-check-label fw-semibold">
    <?= htmlspecialchars($item['descricao']) ?>
  </label>
</div>

<!-- 📝 Observação -->
<textarea class="form-control mt-2 obs-item"
          data-id="<?= $item['id'] ?>"
          placeholder="Observações"
          <?= $bloqueado ? 'readonly' : '' ?>
><?= htmlspecialchars($item['observacao']) ?></textarea>

<!-- 📎 Upload -->
<div class="row mt-3">
  <div class="col-md-6 mb-2">
    <label class="small">📷 Foto</label>
    <input type="file"
           class="form-control upload"
           data-id="<?= $item['id'] ?>"
           data-tipo="foto"
           accept="image/*"
           capture="environment"
           <?= $bloqueado ? 'disabled' : '' ?>>
  </div>

  <div class="col-md-6 mb-2">
    <label class="small">📎 Documento</label>
    <input type="file"
           class="form-control upload"
           data-id="<?= $item['id'] ?>"
           data-tipo="documento"
           accept=".pdf,.jpg,.png,.doc,.docx"
           <?= $bloqueado ? 'disabled' : '' ?>>
  </div>
</div>

<!-- 👀 Preview + anexos -->
<div class="mt-3 d-flex flex-wrap gap-2">

<?php foreach ($anexos as $a): ?>
  <?php if ($a['tipo'] === 'foto'): ?>
    <div class="position-relative">
      <img src="/uploads/checklists/<?= $checklist_id ?>/<?= $item['id'] ?>/<?= $a['arquivo'] ?>"
           class="img-thumbnail foto-thumb"
           data-full="/uploads/checklists/<?= $checklist_id ?>/<?= $item['id'] ?>/<?= $a['arquivo'] ?>"
           style="width:90px;cursor:pointer">

      <?php if (!$bloqueado): ?>
      <button class="btn btn-sm btn-danger remover-anexo position-absolute top-0 end-0"
              data-id="<?= $a['id'] ?>">×</button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="border rounded px-2 py-1 small">
      📎 <?= htmlspecialchars($a['arquivo']) ?>
      <?php if (!$bloqueado): ?>
        <button class="btn btn-sm btn-link text-danger remover-anexo"
                data-id="<?= $a['id'] ?>">remover</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<!-- preview dinâmico -->
<div id="preview-<?= $item['id'] ?>"></div>

</div>

</div>
</div>
<?php endforeach; ?>

<!-- 🔍 Modal Zoom Foto -->
<div class="modal fade" id="modalFoto" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <img id="fotoModal" style="width:100%">
    </div>
  </div>
</div>

<style>
.preview-fotos img { width:120px; }
@media (max-width:768px){
  textarea{font-size:14px}
}
</style>

<script>
// ✔ marcar item
document.querySelectorAll('.check-item').forEach(el => {
  el.addEventListener('change', () => {
    fetch('salvar_item.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        id: el.dataset.id,
        concluido: el.checked ? 1 : 0
      })
    });
  });
});

// ✔ observação
document.querySelectorAll('.obs-item').forEach(el => {
  el.addEventListener('blur', () => {
    fetch('salvar_item.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        id: el.dataset.id,
        observacao: el.value
      })
    });
  });
});

// ✔ upload + preview
document.querySelectorAll('.upload').forEach(el => {
  el.addEventListener('change', () => {
    if (!el.files.length) return;

    const itemId = el.dataset.id;
    const tipo   = el.dataset.tipo;
    const file   = el.files[0];

    const form = new FormData();
    form.append('item_id', itemId);
    form.append('checklist_id', <?= $checklist_id ?>);
    form.append('tipo', tipo);
    form.append('arquivo', file);

    fetch('upload.php', { method:'POST', body: form })
    .then(r => r.json())
    .then(resp => {
      if (!resp.ok) return alert('Erro no upload');

      if (tipo === 'foto') {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'img-thumbnail';
        img.style.width = '90px';
        document.getElementById('preview-'+itemId).appendChild(img);
      }
      el.value='';
    });
  });
});

// 🔍 zoom foto
document.querySelectorAll('.foto-thumb').forEach(img => {
  img.addEventListener('click', () => {
    document.getElementById('fotoModal').src = img.dataset.full;
    new bootstrap.Modal('#modalFoto').show();
  });
});

// ❌ remover anexo
document.querySelectorAll('.remover-anexo').forEach(btn => {
  btn.addEventListener('click', () => {
    if (!confirm('Remover anexo?')) return;
    fetch('excluir_anexo.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id: btn.dataset.id})
    })
    .then(r=>r.json())
    .then(resp=>{ if(resp.ok) location.reload(); });
  });
});
</script>
