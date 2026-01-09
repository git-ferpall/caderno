<?php
/**
 * Preenchimento de checklist
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Garante login */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Checklist */
$checklist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔎 Busca checklist */
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

/* 🔒 Bloqueio se concluído */
$bloqueado = (int)$checklist['concluido'] === 1;

/* 🔎 Busca itens */
$stmt = $mysqli->prepare("
    SELECT
        id,
        descricao,
        ordem,
        concluido,
        observacao
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
    Este checklist já foi finalizado e não pode mais ser alterado.
</div>
<?php endif; ?>

<form>

<?php foreach ($itens as $i): ?>
<div class="card mb-3">
    <div class="card-body">

        <div class="form-check mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   <?= $i['concluido'] ? 'checked' : '' ?>
                   <?= $bloqueado ? 'disabled' : '' ?>>
            <label class="form-check-label fw-bold">
                <?= htmlspecialchars($i['descricao']) ?>
            </label>
        </div>

        <textarea class="form-control"
                  placeholder="Observações"
                  <?= $bloqueado ? 'disabled' : '' ?>><?= htmlspecialchars($i['observacao'] ?? '') ?></textarea>

    </div>
</div>
<?php endforeach; ?>

<?php if (!$bloqueado): ?>
<a href="../fechar/index.php?id=<?= $checklist_id ?>"
   class="btn btn-danger">
    🔒 Finalizar checklist
</a>
<?php endif; ?>

</form>

</div>

</body>
</html>
