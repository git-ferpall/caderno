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
if (!$checklist_id) {
    die('Checklist inválido');
}

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

if (!$checklist) {
    die('Checklist não encontrado ou sem permissão');
}

$bloqueado = (int)$checklist['concluido'] === 1;

/* 🔎 Itens */
$stmt = $mysqli->prepare("
    SELECT
        id,
        descricao,
        ordem,
        concluido,
        observacao,
        permite_observacao,
        permite_foto,
        permite_anexo
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
<div class="alert alert-warning">
    Checklist já finalizado.
</div>
<?php endif; ?>

<form method="post" action="salvar.php">
<input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">
<input type="hidden" name="acao" value="finalizar">

<?php foreach ($itens as $i): ?>
<div class="card mb-3">
    <div class="card-body">

        <!-- ✔ CHECK -->
        <div class="form-check mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   <?= $i['concluido'] ? 'checked' : '' ?>
                   <?= $bloqueado ? 'disabled' : '' ?>>
            <label class="form-check-label fw-bold">
                <?= htmlspecialchars($i['descricao']) ?>
            </label>
        </div>

        <!-- 📝 OBSERVAÇÃO -->
        <?php if ((int)$i['permite_observacao'] === 1): ?>
        <textarea class="form-control mb-2"
                  placeholder="Observações"
                  <?= $bloqueado ? 'disabled' : '' ?>><?= htmlspecialchars($i['observacao'] ?? '') ?></textarea>
        <?php endif; ?>

        <!-- 📸 FOTO -->
        <?php if ((int)$i['permite_foto'] === 1): ?>
        <div class="mb-2">
            <label class="form-label small">📸 Anexar foto</label>
            <input type="file"
                   class="form-control upload-foto"
                   data-item="<?= $i['id'] ?>"
                   accept="image/*"
                   <?= $bloqueado ? 'disabled' : '' ?>>
        </div>
        <?php endif; ?>

        <!-- 📄 DOCUMENTO -->
        <?php if ((int)$i['permite_anexo'] === 1): ?>
        <div class="mb-2">
            <label class="form-label small">📄 Anexar documento</label>
            <input type="file"
                   class="form-control upload-doc"
                   data-item="<?= $i['id'] ?>"
                   <?= $bloqueado ? 'disabled' : '' ?>>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endforeach; ?>


<?php if (!$bloqueado): ?>
<button type="submit" class="btn btn-danger">
    🔒 Finalizar checklist
</button>
<?php endif; ?>

</form>

</div>
</body>
</html>
