<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');

/* Checklist */
$stmt = $mysqli->prepare("
    SELECT id, titulo, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) die('Checklist não encontrado');

$bloqueado = (int)$checklist['concluido'] === 1;

/* Itens */
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
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($checklist['titulo']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">

<h3 class="mb-4">📋 <?= htmlspecialchars($checklist['titulo']) ?></h3>

<?php if ($bloqueado): ?>
<div class="alert alert-warning">Checklist já finalizado.</div>
<?php endif; ?>

<form method="post" action="salvar.php">
<input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">

<?php foreach ($itens as $i): ?>
<div class="card mb-3">
<div class="card-body">

<!-- ✔ CHECK -->
<div class="form-check mb-2">
    <input class="form-check-input"
           type="checkbox"
           name="concluido[<?= $i['id'] ?>]"
           value="1"
           <?= $i['concluido'] ? 'checked' : '' ?>
           <?= $bloqueado ? 'disabled' : '' ?>>
    <label class="form-check-label fw-bold">
        <?= htmlspecialchars($i['descricao']) ?>
    </label>
</div>

<!-- 📝 OBS -->
<?php if ((int)$i['permite_observacao'] === 1): ?>
<textarea class="form-control mb-2"
          name="observacao[<?= $i['id'] ?>]"
          placeholder="Observações"
          <?= $bloqueado ? 'disabled' : '' ?>><?= htmlspecialchars($i['observacao'] ?? '') ?></textarea>
<?php endif; ?>

<!-- 📸 FOTO -->
<?php if ((int)$i['permite_foto'] === 1): ?>
<input type="file"
       class="form-control mb-2 upload-foto"
       data-item="<?= $i['id'] ?>"
       accept="image/*"
       <?= $bloqueado ? 'disabled' : '' ?>>
<?php endif; ?>

<!-- 📄 DOCUMENTO -->
<?php if ((int)$i['permite_anexo'] === 1): ?>
<input type="file"
       class="form-control mb-2 upload-doc"
       data-item="<?= $i['id'] ?>"
       <?= $bloqueado ? 'disabled' : '' ?>>
<?php endif; ?>

</div>
</div>
<?php endforeach; ?>

<?php if (!$bloqueado): ?>
<button class="btn btn-primary" name="acao" value="salvar">
💾 Salvar
</button>

<button class="btn btn-danger" name="acao" value="finalizar">
🔒 Salvar e finalizar
</button>
<?php endif; ?>

</form>
</div>

<script>
document.querySelectorAll('.upload-foto, .upload-doc').forEach(input => {
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        const form = new FormData();
        form.append('item_id', input.dataset.item);
        form.append('tipo', input.classList.contains('upload-foto') ? 'foto' : 'documento');
        form.append('arquivo', file);

        fetch('../itens/upload.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(resp => {
            if (!resp.ok) return alert('Erro no upload');

            const div = document.createElement('div');
            div.className = 'mt-2';

            if (resp.tipo === 'foto') {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'img-thumbnail';
                img.style.maxWidth = '200px';
                div.appendChild(img);
            } else {
                div.textContent = '📄 ' + file.name;
            }

            input.parentNode.appendChild(div);
        });
    });
});
</script>

</body>
</html>
