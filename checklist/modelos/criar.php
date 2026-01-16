<?php
/**
 * Criar / Editar MODELO de checklist
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$modelo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

define('APP_PATH', realpath(__DIR__ . '/../../'));

$modelo = [
    'titulo'    => '',
    'descricao' => '',
    'publico'   => 0
];

$itens = [];

if ($modelo_id) {
    $stmt = $mysqli->prepare("
        SELECT *
        FROM checklist_modelos
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $modelo_id);
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
    $stmt->bind_param("i", $modelo_id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <base href="/">

    <title><?= $modelo_id ? 'Editar' : 'Criar' ?> Modelo de Checklist</title>

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="stylesheet" href="/css/style.css">

    <!-- CSS DO EDITOR -->
    <link rel="stylesheet" href="/checklist/modelos/assets/css/editor.css">
</head>

<body>

<?php require APP_PATH . '/include/loading.php'; ?>
<?php require APP_PATH . '/include/popups.php'; ?>

<div id="conteudo">
<?php require APP_PATH . '/include/menu.php'; ?>

<div class="container py-4 page-content">
<main class="sistema">

<h2 class="main-title cor-branco">
    ✏️ <?= $modelo_id ? 'Editar' : 'Criar' ?> modelo de checklist
</h2>

<form action="/checklist/modelos/salvar.php"
      method="POST"
      class="main-form">

<?php if ($modelo_id): ?>
<input type="hidden" name="modelo_id" value="<?= $modelo_id ?>">
<?php endif; ?>

<!-- TÍTULO -->
<div class="form-campo">
    <label>Título</label>
    <input class="form-text"
           type="text"
           name="titulo"
           required
           value="<?= htmlspecialchars($modelo['titulo']) ?>">
</div>

<!-- DESCRIÇÃO -->
<div class="form-campo">
    <label>Descrição</label>
    <textarea class="form-text"
              name="descricao"
              rows="3"><?= htmlspecialchars($modelo['descricao']) ?></textarea>
</div>

<!-- PÚBLICO -->
<div class="form-campo">
    <label>
        <input type="checkbox"
               name="publico"
               value="1"
               <?= $modelo['publico'] ? 'checked' : '' ?>>
        Modelo padrão do sistema
    </label>
</div>

<hr>

<h3>📋 Estrutura do checklist</h3>

<div id="itens">

<?php foreach ($itens as $i): ?>
<?php $key = 'id_' . $i['id']; ?>

<?php if ($i['tipo'] === 'sessao'): ?>

<div class="sessao-card" data-key="<?= $key ?>">
    <span class="handle">☰</span>

    <input type="hidden" name="item_key[]" value="<?= $key ?>">
    <input type="hidden" name="item_tipo[<?= $key ?>]" value="sessao">

    <input type="text"
           name="item_desc[<?= $key ?>]"
           value="<?= htmlspecialchars($i['descricao']) ?>"
           placeholder="Nome da sessão">
</div>

<?php else: ?>

<div class="item-card" data-key="<?= $key ?>">

    <span class="handle">☰</span>

    <input type="hidden" name="item_key[]" value="<?= $key ?>">

    <div class="item-header">
        <input type="text"
               class="item-title"
               name="item_desc[<?= $key ?>]"
               value="<?= htmlspecialchars($i['descricao']) ?>"
               required>

        <select name="item_tipo[<?= $key ?>]"
                class="item-tipo">
            <option value="texto_longo" <?= $i['tipo']=='texto_longo'?'selected':'' ?>>Texto longo</option>
            <option value="texto_curto" <?= $i['tipo']=='texto_curto'?'selected':'' ?>>Texto curto</option>
            <option value="data" <?= $i['tipo']=='data'?'selected':'' ?>>Data</option>
            <option value="unica" <?= $i['tipo']=='unica'?'selected':'' ?>>Única escolha</option>
            <option value="multipla" <?= $i['tipo']=='multipla'?'selected':'' ?>>Múltipla escolha</option>
            <option value="nota_estrela" <?= $i['tipo']=='nota_estrela'?'selected':'' ?>>Nota ⭐</option>
            <option value="nota_0_10" <?= $i['tipo']=='nota_0_10'?'selected':'' ?>>Nota 0–10</option>
        </select>

        <button type="button"
                class="btn-remover-text"
                onclick="this.closest('.item-card').remove()">🗑</button>
    </div>

    <div class="item-body"></div>
</div>

<?php endif; ?>
<?php endforeach; ?>

</div>

<!-- BOTÕES -->
<div class="form-submit form-submit-equal mt-4">

    <button type="button"
            class="main-btn fundo-azul"
            onclick="addPergunta()">
        + Pergunta
    </button>

    <button type="button"
            class="main-btn fundo-roxo"
            onclick="addSessao()">
        + Sessão
    </button>

    <button type="submit"
            class="main-btn fundo-verde">
        Salvar
    </button>

</div>

</form>
</main>
</div>
</div>

<?php require APP_PATH . '/include/footer.php'; ?>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="/js/popups.js"></script>
<script src="/js/script.js"></script>
<script src="/checklist/modelos/assets/js/editor.js"></script>
<script src="/checklist/modelos/assets/js/editor-add.js"></script>
<script src="/checklist/modelos/assets/js/editor-sortable.js"></script>

</body>
</html>
