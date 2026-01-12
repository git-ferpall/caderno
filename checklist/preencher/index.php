<?php
/**
 * Preenchimento de checklist
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = (int)($_GET['id'] ?? 0);
if (!$checklist_id) die('Checklist inválido');

/* 🔎 Checklist */
$stmt = $mysqli->prepare("
    SELECT id, titulo, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) die('Checklist não encontrado ou sem permissão');

$bloqueado = (int)$checklist['concluido'] === 1;

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
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($checklist['titulo']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.bloqueado {
    pointer-events: none;
    opacity: .65;
}
</style>
</head>

<body class="bg-light">
<div class="container py-4">

<h3 class="mb-4">📋 <?= htmlspecialchars($checklist['titulo']) ?></h3>

<?php if ($bloqueado): ?>
<div class="alert alert-warning">
    Checklist finalizado. Apenas visualização.
</div>
<?php endif; ?>

<form method="post" action="salvar.php">
<input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">

<?php foreach ($itens as $i): ?>
<div class="card mb-3 <?= $bloqueado ? 'bloqueado' : '' ?>">
<div class="card-body">

<!-- ✔ CHECK -->
<div class="form-check mb-2">
    <input class="form-check-input"
           type="checkbox"
           name="concluido[<?= $i['id'] ?>]"
           value="1"
           <?= $i['concluido'] ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold">
        <?= htmlspecialchars($i['descricao']) ?>
    </label>
</div>

<!-- 📝 OBSERVAÇÃO -->
<?php if ((int)$i['permite_observacao'] === 1): ?>
<textarea class="form-control mb-2"
          name="observacao[<?= $i['id'] ?>]"
          placeholder="Observações"><?= htmlspecialchars($i['observacao'] ?? '') ?></textarea>
<?php endif; ?>

<!-- 📸 FOTO -->
<?php if ((int)$i['permite_foto'] === 1): ?>
<div class="mb-2">
    <label class="form-label small">📸 Anexar foto</label>
    <input type="file"
           class="form-control upload-foto"
           data-item="<?= $i['id'] ?>"
           accept="image/*">
</div>
<?php endif; ?>

<!-- 📄 DOCUMENTO -->
<?php if ((int)$i['permite_anexo'] === 1): ?>
<div class="mb-2">
    <label class="form-label small">📄 Anexar documento</label>
    <input type="file"
           class="form-control upload-doc"
           data-item="<?= $i['id'] ?>">
</div>
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

        fetch('../itens/upload.php', {
            method: 'POST',
            body: form
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp.ok) {
                alert(resp.erro || 'Erro no upload');
                return;
            }

            const box = document.createElement('div');
            box.className = 'mt-2';

            if (resp.tipo === 'foto') {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'img-thumbnail';
                img.style.maxWidth = '200px';
                box.appendChild(img);
            } else {
                box.textContent = '📄 ' + file.name;
            }

            input.closest('.card-body').appendChild(box);
        });
    });

});
</script>

</body>
</html>
