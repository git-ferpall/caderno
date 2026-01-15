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
        .form-submit-equal {
            display: flex;
            gap: 16px;
        }

        .form-submit-equal .main-btn {
            flex: 1;                 /* 🔥 todos com o mesmo tamanho */
            text-align: center;
            justify-content: center;
        }
        main.sistema {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 18px;
            padding: 28px;

            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(4px);
        }

        .page-content {
            margin-top: 80px;
        }

        .form-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
        }

        .item-main {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .item-desc {
            flex: 1;
            min-width: 0; /* ESSENCIAL */
        }

        .form-opcoes,
        .btn-remover-text {
            flex-shrink: 0;
        }

        .config-multipla {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
        }

        .config-multipla textarea,
        .config-multipla input {
            width: 100%;
        }


    </style>

</head>
<body>

<?php require APP_PATH . '/include/loading.php'; ?>
<?php require APP_PATH . '/include/popups.php'; ?>

<div id="conteudo">

    <?php require APP_PATH . '/include/menu.php'; ?>
    <div class="container py-4 page-content">    
        <main class="sistema">

            <!-- TÍTULO DA PÁGINA -->
            <div class="page-title">
                <h2 class="main-title cor-branco">
                    ✏️ <?= $modelo_id ? 'Editar' : 'Criar' ?> modelo de checklist
                </h2>
            </div>

            <!-- FORMULÁRIO -->
            <form action="/checklist/modelos/salvar.php"
                method="POST"
                class="main-form container">

                <?php if ($modelo_id > 0): ?>
                    <input type="hidden" name="modelo_id" value="<?= $modelo_id ?>">
                <?php endif; ?>

                <!-- TÍTULO -->
                <div class="form-campo">
                    <label for="titulo">Título</label>
                    <input
                        class="form-text"
                        type="text"
                        name="titulo"
                        id="titulo"
                        placeholder="Título do checklist"
                        required
                        value="<?= htmlspecialchars($modelo['titulo']) ?>"
                    >
                </div>

                <!-- DESCRIÇÃO -->
                <div class="form-campo">
                    <label for="descricao">Descrição</label>
                    <textarea
                        name="descricao"
                        id="descricao"
                        class="form-text"
                        rows="3"
                        placeholder="Descrição opcional"
                    ><?= htmlspecialchars($modelo['descricao']) ?></textarea>
                </div>
                    
                <!-- PÚBLICO -->
                <div class="form-campo">
                    <label>
                        <input
                            type="checkbox"
                            name="publico"
                            value="1"
                            <?= $modelo['publico'] ? 'checked' : '' ?>
                        >
                        Modelo padrão do sistema
                    </label>
                </div>

                <!-- ITENS -->
                <h2 class="mt-4">📋 Itens do checklist</h2>

                <div id="itens">

                    <?php foreach ($itens as $i): ?>
                        <?php $key = 'id_' . $i['id']; ?>

                        <div class="form-campo item" data-key="<?= $key ?>">
                            <div class="form-box">

                                <div class="item-main">

                                    <span class="handle">☰</span>

                                    <input type="hidden" name="item_key[]" value="<?= $key ?>">
                                    <input type="hidden" name="item_tipo[<?= $key ?>]" value="<?= $i['tipo'] ?>">

                                    <input class="form-text item-desc" type="text"
                                        name="item_desc[<?= $key ?>]"
                                        value="<?= htmlspecialchars($i['descricao']) ?>"
                                        required>

                                    <div class="form-opcoes">
                                        <label>
                                            <input type="checkbox" class="opcao-unica opcao-obs"
                                                name="item_obs[<?= $key ?>]" value="1"
                                                <?= $i['permite_observacao'] ? 'checked' : '' ?>>
                                            Obs
                                        </label>

                                        <label>
                                            <input type="checkbox" class="opcao-unica opcao-foto"
                                                name="item_foto[<?= $key ?>]" value="1"
                                                <?= ($i['tipo'] === 'texto' && $i['permite_foto']) ? 'checked' : '' ?>>
                                            Foto
                                        </label>

                                        <label>
                                            <input type="checkbox" class="opcao-unica opcao-data"
                                                <?= $i['tipo'] === 'data' ? 'checked' : '' ?>>
                                            Data
                                        </label>

                                        <label>
                                            <input type="checkbox" class="opcao-unica opcao-multipla"
                                                <?= $i['tipo'] === 'multipla' ? 'checked' : '' ?>>
                                            Múltipla
                                        </label>
                                    </div>

                                    <button type="button"
                                            class="btn-remover-text"
                                            onclick="this.closest('.item').remove()">
                                        🗑 Remover
                                    </button>

                                </div>

                                <div class="config-multipla"
                                    style="display: <?= $i['tipo'] === 'multipla' ? 'block' : 'none' ?>">

                                    <label>Opções (uma por linha)</label>
                                    <textarea
                                        name="item_opcoes[<?= $key ?>]"
                                        class="form-text"
                                        rows="3"><?= htmlspecialchars($i['opcoes'] ?? '') ?></textarea>

                                    <label>Quantas opções podem ser selecionadas?</label>
                                    <input
                                        type="number"
                                        min="1"
                                        name="item_max[<?= $key ?>]"
                                        class="form-text"
                                        value="<?= (int)($i['max_selecoes'] ?? 1) ?>">
                                </div>

                            </div>
                        </div>


                    <?php endforeach; ?>

                </div>

                <!-- BOTÕES -->
                <div class="form-submit form-submit-equal mt-4">

                    <button
                        type="button"
                        class="main-btn fundo-azul"
                        onclick="addItem()"
                    >
                        + Item
                    </button>

                    <button
                        type="submit"
                        class="main-btn fundo-verde"
                    >
                        Salvar
                    </button>

                    <a
                        href="/checklist/modelos/index.php"
                        class="main-btn"
                        style="background-color:#dc3545; color:#fff;"
                    >
                        Cancelar
                    </a>

                </div>

            </form>
        </main>
    </div>    
</div>


<?php require APP_PATH . '/include/footer.php'; ?>
<script src="/js/jquery.js"></script>
<script src="/js/main.js"></script>
<script src="/js/popups.js"></script>
<script src="/js/script.js"></script>
<!-- SORTABLE -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.item').forEach(item => {
        const hiddenTipo = item.querySelector('input[name^="item_tipo"]');
        const cfg = item.querySelector('.config-multipla');

        if (hiddenTipo && cfg) {
            cfg.style.display = (hiddenTipo.value === 'multipla') ? 'block' : 'none';
        }
    });

});
</script>


<script>
function addItem() {
    const key = 'new_' + Date.now();

    const div = document.createElement('div');
    div.className = 'form-campo item';
    div.dataset.key = key;

    div.innerHTML = `
        <div class="form-box">

            <div class="item-main">

                <span class="handle">☰</span>

                <input type="hidden" name="item_key[]" value="${key}">
                <input type="hidden" name="item_tipo[${key}]" value="texto">

                <input class="form-text item-desc" type="text"
                    name="item_desc[${key}]"
                    placeholder="Descrição do item"
                    required>

                <div class="form-opcoes">
                    <label>
                        <input type="checkbox" class="opcao-unica opcao-obs"
                            name="item_obs[${key}]" value="1" checked>
                        Obs
                    </label>

                    <label>
                        <input type="checkbox" class="opcao-unica opcao-foto"
                            name="item_foto[${key}]" value="1">
                        Foto
                    </label>

                    <label>
                        <input type="checkbox" class="opcao-unica opcao-data">
                        Data
                    </label>

                    <label>
                        <input type="checkbox" class="opcao-unica opcao-multipla">
                        Múltipla
                    </label>
                </div>

                <button type="button"
                        class="btn-remover-text"
                        onclick="this.closest('.item').remove()">
                    🗑
                </button>

            </div>

            <div class="config-multipla" style="display:none">
                <label>Opções (uma por linha)</label>
                <textarea class="form-text" rows="3"
                    name="item_opcoes[${key}]"></textarea>

                <label>Quantas opções podem ser selecionadas?</label>
                <input type="number" min="1"
                    class="form-text"
                    name="item_max[${key}]"
                    value="1">
            </div>

        </div>

    `;

    document.getElementById('itens').appendChild(div);
}
</script>


<script>
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('opcao-unica')) return;

        const item = e.target.closest('.item');
        if (!item) return;

        const hiddenTipo = item.querySelector('input[name^="item_tipo"]');

        // Desmarca todas as outras opções
        item.querySelectorAll('.opcao-unica').forEach(cb => {
            if (cb !== e.target) cb.checked = false;
        });

        // Define o tipo
        if (e.target.classList.contains('opcao-data')) {
            hiddenTipo.value = 'data';
        } else if (e.target.classList.contains('opcao-multipla')) {
            hiddenTipo.value = 'multipla';
        } else {
            hiddenTipo.value = 'texto';
        }

        // Exibe configuração da múltipla somente se necessário
        const cfg = item.querySelector('.config-multipla');
        if (cfg) {
            cfg.style.display = (hiddenTipo.value === 'multipla') ? 'block' : 'none';
        }
    });
</script>





</body>
</html>
