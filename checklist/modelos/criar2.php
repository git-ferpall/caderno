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

/* 🔒 BASE DO SISTEMA */
define('APP_PATH', realpath(__DIR__ . '/../../'));

$modelo = [
    'titulo'    => '',
    'descricao' => '',
    'publico'   => 0
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
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $id ? 'Editar' : 'Criar' ?> Modelo de Checklist</title>

<link rel="icon" type="image/png" href="/img/logo-icon.png">

<!-- CSS DO SISTEMA -->
<link rel="stylesheet" href="/css/style.css">
<link rel="stylesheet" href="/css/silo.css">

<style>
.handle { cursor: grab; font-size: 18px; }
.form-opcoes { display:flex; gap:12px; }
.btn-remover { padding:4px 10px; }
</style>

</head>
<body>

<?php require APP_PATH . '/include/loading.php'; ?>
<?php require APP_PATH . '/include/popups.php'; ?>

<div id="conteudo">
<?php require APP_PATH . '/include/menu.php'; ?>

<main class="sistema">
<div class="page-title">
    <h2 class="main-title cor-branco">
        ✏️ <?= $id ? 'Editar' : 'Criar' ?> modelo de checklist
    </h2>
</div>

<form action="salvar.php" method="POST" class="main-form container">
<input type="hidden" name="id" value="<?= $id ?>">

<!-- TÍTULO -->
<div class="form-campo">
    <label for="titulo">Título</label>
    <input class="form-text"
           type="text"
           name="titulo"
           id="titulo"
           placeholder="Título do checklist"
           required
           value="<?= htmlspecialchars($modelo['titulo']) ?>">
</div>

<!-- DESCRIÇÃO -->
<div class="form-campo">
    <label for="descricao">Descrição</label>
    <textarea name="descricao"
              id="descricao"
              class="form-text"
              rows="3"
              placeholder="Descrição opcional"><?= htmlspecialchars($modelo['descricao']) ?></textarea>
</div>

<!-- PÚBLICO -->
<div class="form-campo">
    <label>
        <input type="checkbox" name="publico" value="1" <?= $modelo['publico'] ? 'checked' : '' ?>>
        Modelo padrão do sistema
    </label>
</div>

<h2>📋 Itens do checklist</h2>

<div id="itens">
<?php foreach ($itens as $i):
    $key = 'id_' . $i['id'];
?>
<div class="form-campo item" data-key="<?= $key ?>">
    <div class="form-box">
        <span class="handle">☰</span>

        <input type="hidden" name="item_key[]" value="<?= $key ?>">

        <input class="form-text"
               type="text"
               name="item_desc[<?= $key ?>]"
               value="<?= htmlspecialchars($i['descricao']) ?>"
               required>

        <div class="form-opcoes">
            <label>
                <input type="checkbox" class="opcao-item"
                       name="item_obs[<?= $key ?>]" value="1"
                       <?= $i['permite_observacao'] ? 'checked' : '' ?>>
                Obs
            </label>

            <label>
                <input type="checkbox" class="opcao-item"
                       name="item_foto[<?= $key ?>]" value="1"
                       <?= $i['permite_foto'] ? 'checked' : '' ?>>
                Foto
            </label>

            <label>
                <input type="checkbox" class="opcao-item"
                       name="item_anexo[<?= $key ?>]" value="1"
                       <?= $i['permite_anexo'] ? 'checked' : '' ?>>
                Doc
            </label>
        </div>

        <button type="button"
                class="main-btn fundo-vermelho btn-remover"
                onclick="this.closest('.item').remove()">×</button>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- BOTÕES -->
<div class="form-submit">
    <button type="button" class="main-btn fundo-cinza" onclick="addItem()">+ Item</button>
    <button type="submit" class="main-btn fundo-verde">Salvar</button>
</div>

</form>
</main>
</div>

<?php require APP_PATH . '/include/footer.php'; ?>
<?php require APP_PATH . 'include/imports.php'; ?>

<!-- SORTABLE -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
function addItem() {
    const key = 'new_' + Date.now();

    const div = document.createElement('div');
    div.className = 'form-campo item';
    div.dataset.key = key;

    div.innerHTML = `
        <div class="form-box">
            <span class="handle">☰</span>

            <input type="hidden" name="item_key[]" value="${key}">

            <input class="form-text" type="text"
                   name="item_desc[${key}]"
                   placeholder="Descrição do item"
                   required>

            <div class="form-opcoes">
                <label><input type="checkbox" class="opcao-item" name="item_obs[${key}]" value="1" checked> Obs</label>
                <label><input type="checkbox" class="opcao-item" name="item_foto[${key}]" value="1"> Foto</label>
                <label><input type="checkbox" class="opcao-item" name="item_anexo[${key}]" value="1"> Doc</label>
            </div>

            <button type="button"
                    class="main-btn fundo-vermelho btn-remover"
                    onclick="this.closest('.item').remove()">×</button>
        </div>
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
