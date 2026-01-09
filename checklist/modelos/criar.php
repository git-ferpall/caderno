<?php
/**
 * Criar / Editar MODELO de checklist
 * Stack: MySQLi + protect.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login obrigatório */
$user = require_login();
$user_id = (int)$user->sub;

/* 📥 Modelo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$modelo = [
    'titulo' => '',
    'descricao' => '',
    'publico' => 0
];

$itens = [];

if ($id) {
    $stmt = $mysqli->prepare("
        SELECT *
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $modelo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$modelo) die('Modelo não encontrado');

    if (!$modelo['publico'] && (int)$modelo['criado_por'] !== $user_id) {
        die('Sem permissão');
    }

    $stmt = $mysqli->prepare("
        SELECT *
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
<title><?= $id ? 'Editar' : 'Criar' ?> modelo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
.handle { cursor: grab }
</style>
</head>

<body class="bg-light">

<div class="container py-4">

<h3>✏️ <?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</h3>

<form method="post" action="../itens/salvar.php">

<input type="hidden" name="modelo_id" value="<?= $modelo_id ?>">

<div class="mb-3">
    <label class="form-label">Descrição do item</label>
    <input type="text"
           name="descricao"
           class="form-control"
           required>
</div>

<div class="form-check">
    <input class="form-check-input"
           type="checkbox"
           name="permite_observacao"
           value="1"
           checked>
    <label class="form-check-label">
        Permitir observação
    </label>
</div>

<div class="form-check">
    <input class="form-check-input"
           type="checkbox"
           name="permite_foto"
           value="1">
    <label class="form-check-label">
        Permitir foto 📸
    </label>
</div>

<div class="form-check mb-3">
    <input class="form-check-input"
           type="checkbox"
           name="permite_anexo"
           value="1">
    <label class="form-check-label">
        Permitir documento 📄
    </label>
</div>

<button type="submit" class="btn btn-primary">
    ➕ Adicionar item
</button>

</form>


</div>

<script>
function addItem() {
    const key = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

    const div = document.createElement('div');
    div.className = 'input-group mb-2 item';
    div.innerHTML = `
        <span class="input-group-text handle">☰</span>

        <input type="hidden" name="item_key[]" value="${key}">
        <input type="text" name="item_desc[${key}]" class="form-control" required>

        <span class="input-group-text">
            <input type="checkbox" name="item_obs[${key}]" value="1" checked>
            <small class="ms-1">Obs</small>
        </span>

        <button type="button" class="btn btn-danger"
                onclick="this.closest('.item').remove()">×</button>
    `;
    document.getElementById('itens').appendChild(div);
}

new Sortable(document.getElementById('itens'), {
    handle: '.handle',
    animation: 150
});
</script>

</body>
</html>
