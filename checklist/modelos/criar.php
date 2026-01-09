<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/*
 * 🔒 Garante login:
 * - se não estiver logado → redirect
 * - se estiver logado → retorna JWT (claims)
 */
$user = require_login();

/* 👤 ID do usuário autenticado */
$user_id = (int) $user->sub;

/* 📥 ID do modelo (edição) */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* 🧠 Dados padrão */
$modelo = [
    'titulo' => '',
    'descricao' => '',
    'publico' => 0
];

$itens = [];

/* 🔎 Se edição, carrega modelo e itens */
if ($id) {

    $stmt = $mysqli->prepare("
        SELECT titulo, descricao, publico, criado_por
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $modelo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$modelo) {
        die('Modelo não encontrado');
    }

    /* Segurança: só pode editar modelo próprio */
    if (!$modelo['publico'] && (int)$modelo['criado_por'] !== (int)$user_id) {
        http_response_code(403);
        die('Você não tem permissão para editar este modelo');
    }

    $stmt = $mysqli->prepare("
        SELECT id, descricao
        FROM checklist_modelo_itens
        WHERE modelo_id = ?
        ORDER BY ordem
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
.item { cursor: default; }
.handle { cursor: grab; user-select: none; }
</style>
</head>

<body class="bg-light">

<div class="container py-4">

<h3 class="mb-4"><?= $id ? '✏️ Editar' : '➕ Criar' ?> modelo de checklist</h3>

<form method="post" action="salvar.php">

<input type="hidden" name="id" value="<?= $id ?>">

<div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="titulo" class="form-control"
           value="<?= htmlspecialchars($modelo['titulo']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Descrição</label>
    <textarea name="descricao" class="form-control"
              rows="3"><?= htmlspecialchars($modelo['descricao']) ?></textarea>
</div>

<div class="form-check mb-4">
    <input type="checkbox" name="publico" class="form-check-input"
           <?= $modelo['publico'] ? 'checked' : '' ?>>
    <label class="form-check-label">
        Modelo padrão do sistema
    </label>
</div>

<hr>

<h5 class="mb-3">📋 Itens do checklist</h5>

<div id="itens">

<?php foreach ($itens as $idx => $i): ?>
<div class="input-group mb-2 item">
    <span class="input-group-text handle">☰</span>

    <input type="hidden" name="item_id[]" value="<?= $i['id'] ?>">
    <input type="text" name="item_desc[]" class="form-control"
           value="<?= htmlspecialchars($i['descricao'] ?? '') ?>" required>

    <span class="input-group-text">
        <input type="checkbox"
            name="item_obs[<?= $idx ?>]"
            value="1"
            <?= ($i['permite_observacao'] ?? 1) ? 'checked' : '' ?>>

        <small class="ms-1">Obs</small>
    </span>

    <button type="button" class="btn btn-danger"
            onclick="removerItem(this)">×</button>
</div>

<?php endforeach; ?>

</div>

<button type="button"
        class="btn btn-outline-secondary mb-3"
        onclick="addItem()">
➕ Adicionar item
</button>

<hr>

<button class="btn btn-success">💾 Salvar modelo</button>
<a href="index.php" class="btn btn-secondary">Cancelar</a>

</form>

</div>

<script>
function addItem() {
    const idx = document.querySelectorAll('#itens .item').length;

    const div = document.createElement('div');
    div.className = 'input-group mb-2 item';
    div.innerHTML = `
        <span class="input-group-text handle">☰</span>

        <input type="hidden" name="item_id[]" value="">
        <input type="text" name="item_desc[]" class="form-control" required>

        <span class="input-group-text">
            <input type="checkbox" name="item_obs[${idx}]" value="1" checked>
            <small class="ms-1">Obs</small>
        </span>

        <button type="button" class="btn btn-danger"
                onclick="removerItem(this)">×</button>
    `;
    document.getElementById('itens').appendChild(div);
}



function removerItem(btn) {
    btn.closest('.item').remove();
}

/* 🔀 Drag & Drop */
new Sortable(document.getElementById('itens'), {
    animation: 150,
    handle: '.handle',
    ghostClass: 'bg-light'
});
</script>

</body>
</html>
