<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

session_start();

/* 🔐 user_id */
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) die('Usuário não autenticado');

/* 📥 edição */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* 🧠 Modelo */
$modelo = [
    'titulo' => '',
    'descricao' => '',
    'publico' => 0
];

$itens = [];

if ($id) {
    $stmt = $mysqli->prepare("SELECT * FROM checklist_modelos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $modelo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $mysqli->prepare("
        SELECT * FROM checklist_modelo_itens
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
<title>Modelo de Checklist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.item { cursor: move; }
</style>
</head>

<body class="bg-light">
<div class="container py-4">

<h3><?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</h3>

<form method="post" action="salvar.php">

<input type="hidden" name="id" value="<?= $id ?>">

<div class="mb-3">
<label class="form-label">Título</label>
<input type="text" name="titulo" class="form-control"
       value="<?= htmlspecialchars($modelo['titulo']) ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Descrição</label>
<textarea name="descricao" class="form-control"><?= htmlspecialchars($modelo['descricao']) ?></textarea>
</div>

<div class="form-check mb-4">
<input type="checkbox" name="publico" class="form-check-input"
       <?= $modelo['publico'] ? 'checked' : '' ?>>
<label class="form-check-label">Modelo padrão do sistema</label>
</div>

<hr>

<h5>Itens do checklist</h5>

<div id="itens">
<?php foreach ($itens as $i): ?>
<div class="input-group mb-2 item">
    <input type="hidden" name="item_id[]" value="<?= $i['id'] ?>">
    <input type="text" name="item_desc[]" class="form-control"
           value="<?= htmlspecialchars($i['descricao']) ?>" required>
    <button type="button" class="btn btn-danger" onclick="removerItem(this)">×</button>
</div>
<?php endforeach ?>
</div>

<button type="button" class="btn btn-outline-secondary mb-3" onclick="addItem()">
➕ Adicionar item
</button>

<hr>

<button class="btn btn-success">Salvar modelo</button>
<a href="index.php" class="btn btn-secondary">Cancelar</a>

</form>
</div>

<script>
function addItem() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2 item';
    div.innerHTML = `
        <input type="hidden" name="item_id[]" value="">
        <input type="text" name="item_desc[]" class="form-control" required>
        <button type="button" class="btn btn-danger" onclick="removerItem(this)">×</button>
    `;
    document.getElementById('itens').appendChild(div);
}

function removerItem(btn) {
    btn.closest('.item').remove();
}
</script>

</body>
</html>
