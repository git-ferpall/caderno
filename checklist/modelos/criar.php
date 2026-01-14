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
$modelo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


/* 🔒 BASE DO SISTEMA */
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">

    <title><?= $modelo_id ? 'Editar' : 'Criar' ?> Modelo de Checklist</title>

    <link rel="icon" type="image/png" href="/img/logo-icon.png">

    <!-- CSS DO SISTEMA -->
    <link rel="stylesheet" href="/css/style.css">

    <style>
        .handle { cursor: grab; font-size: 18px; }
        .form-opcoes { display:flex; gap:12px; }
        .btn-remover { padding:4px 10px; }

        .btn-remover-text {
            border: none;
            border-radius: 20px;
            padding: 6px 14px;
            background: #f44336;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
        }

        .btn-remover-text:hover {
            background: #d32f2f;
        }

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
            ✏️ <?= $modelo_id ? 'Editar' : 'Criar' ?> modelo de checklist
        </h2>
    </div>

    <form action="/checklist/modelos/salvar.php" method="POST" class="main-form container">
    <?php if ($modelo_id > 0): ?>
    <input type="hidden" name="modelo_id" value="<?= $modelo_id ?>">

    <?php endif; ?>


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

            </div>

            <button type="button"
                    class="btn-remover-text"
                    onclick="this.closest('.item').remove()">
                🗑 Remover
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

    <!-- BOTÕES -->
    <div class="form-submit">
        <button type="button" class="main-btn fundo-azul" onclick="addItem()">+ Item</button>
        <button type="button"
            class="main-btn fundo-verde"
            onclick="this.closest('form').submit()">
        Salvar
    </button>
    <a href="index.php" class="main-btn fundo-cinza">
        Cancelar
    </a>
    
    </div>

</form>
</main>
</div>

<?php require APP_PATH . '/include/footer.php'; ?>
<script src="/js/jquery.js"></script>
<script src="/js/main.js"></script>
<script src="/js/popups.js"></script>
<script src="/js/script.js"></script>
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
            </div>

            <button type="button"
                    class="btn-remover-text"
                    onclick="this.closest('.item').remove()">
                🗑 Remover
            </button>
        </div>
    `;

    document.getElementById('itens').appendChild(div);
}

new Sortable(document.getElementById('itens'), {
    handle: '.handle',
    animation: 150
});
</script>
<script>
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('opcao-item')) return;

    const container = e.target.closest('.form-opcoes');
    if (!container) return;

    if (e.target.checked) {
        container.querySelectorAll('.opcao-item').forEach(cb => {
            if (cb !== e.target) cb.checked = false;
        });
    }
});
</script>



</body>
</html>
