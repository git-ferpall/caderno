<?php
/**
 * Criar / Editar MODELO de checklist
 * Stack: MySQLi + protect.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 BASE DO SISTEMA (CONFIRMADA) */
define('APP_PATH', realpath(__DIR__ . '/../../'));

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
    <title><?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</title>

    <!-- Bootstrap + Sortable -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- CSS do sistema -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/silo.css">

    <style>
        .handle { cursor: grab }
    </style>
</head>

<body>

<?php require APP_PATH . '/include/loading.php'; ?>
<?php require APP_PATH . '/include/popups.php'; ?>

<div id="conteudo">
    <?php require APP_PATH . '/include/menu.php'; ?>

    <!-- ESTRUTURA CORRETA DO SILO -->
    <main id="silo" class="sistema">
        <div class="sistema-main silo">
            <div class="silo-dados container py-4">

                <h3 class="mb-4">✏️ <?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</h3>

                <form method="post" action="salvar.php">

                    <input type="hidden" name="id" value="<?= $id ?>">

                    <!-- TÍTULO -->
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text"
                               name="titulo"
                               class="form-control"
                               value="<?= htmlspecialchars($modelo['titulo'] ?? '') ?>"
                               required>
                    </div>

                    <!-- DESCRIÇÃO -->
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao"
                                  class="form-control"
                                  rows="3"><?= htmlspecialchars($modelo['descricao'] ?? '') ?></textarea>
                    </div>

                    <!-- PÚBLICO -->
                    <div class="form-check mb-4">
                        <input type="checkbox"
                               name="publico"
                               class="form-check-input"
                               <?= $modelo['publico'] ? 'checked' : '' ?>>
                        <label class="form-check-label">
                            Modelo padrão do sistema
                        </label>
                    </div>

                    <hr>

                    <!-- ITENS -->
                    <h5 class="mb-3">📋 Itens do checklist</h5>

                    <div id="itens">
                    <?php foreach ($itens as $i):
                        $key = 'id_' . $i['id'];
                    ?>
                        <div class="input-group mb-2 item">
                            <span class="input-group-text handle">☰</span>

                            <input type="hidden" name="item_key[]" value="<?= $key ?>">

                            <input type="text"
                                   name="item_desc[<?= $key ?>]"
                                   class="form-control"
                                   value="<?= htmlspecialchars($i['descricao']) ?>"
                                   required>

                            <span class="input-group-text" data-grupo="<?= $key ?>">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input opcao-item"
                                           type="checkbox"
                                           name="item_obs[<?= $key ?>]"
                                           value="1"
                                           <?= $i['permite_observacao'] ? 'checked' : '' ?>>
                                    <small class="ms-1">Obs</small>
                                </div>

                                <div class="form-check form-check-inline mb-0 ms-2">
                                    <input class="form-check-input opcao-item"
                                           type="checkbox"
                                           name="item_foto[<?= $key ?>]"
                                           value="1"
                                           <?= !empty($i['permite_foto']) ? 'checked' : '' ?>>
                                    <small class="ms-1">Foto</small>
                                </div>

                                <div class="form-check form-check-inline mb-0 ms-2">
                                    <input class="form-check-input opcao-item"
                                           type="checkbox"
                                           name="item_anexo[<?= $key ?>]"
                                           value="1"
                                           <?= !empty($i['permite_anexo']) ? 'checked' : '' ?>>
                                    <small class="ms-1">Doc</small>
                                </div>
                            </span>

                            <button type="button"
                                    class="btn btn-danger"
                                    onclick="this.closest('.item').remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <button type="button"
                            class="btn btn-outline-primary mb-3"
                            onclick="addItem()">
                        ➕ Adicionar item
                    </button>

                    <hr>

                    <button class="btn btn-success">💾 Salvar modelo</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>

                </form>

            </div>
        </div>
    </main>

    <?php require APP_PATH . '/include/imports.php'; ?>
</div>

<?php require APP_PATH . '/include/footer.php'; ?>

<script>
function addItem() {
    const key = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

    const div = document.createElement('div');
    div.className = 'input-group mb-2 item';
    div.innerHTML = `
        <span class="input-group-text handle">☰</span>

        <input type="hidden" name="item_key[]" value="${key}">

        <input type="text" name="item_desc[${key}]" class="form-control" required>

        <span class="input-group-text" data-grupo="${key}">
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input opcao-item"
                       type="checkbox"
                       name="item_obs[${key}]"
                       value="1"
                       checked>
                <small class="ms-1">Obs</small>
            </div>

            <div class="form-check form-check-inline mb-0 ms-2">
                <input class="form-check-input opcao-item"
                       type="checkbox"
                       name="item_foto[${key}]"
                       value="1">
                <small class="ms-1">Foto</small>
            </div>

            <div class="form-check form-check-inline mb-0 ms-2">
                <input class="form-check-input opcao-item"
                       type="checkbox"
                       name="item_anexo[${key}]"
                       value="1">
                <small class="ms-1">Doc</small>
            </div>
        </span>

        <button type="button"
                class="btn btn-danger"
                onclick="this.closest('.item').remove()">×</button>
    `;
    document.getElementById('itens').appendChild(div);
}

new Sortable(document.getElementById('itens'), {
    handle: '.handle',
    animation: 150
});

/* 🔒 Apenas 1 opção por item */
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('opcao-item')) return;

    const grupo = e.target.closest('[data-grupo]');
    if (!grupo) return;

    if (e.target.checked) {
        grupo.querySelectorAll('.opcao-item').forEach(cb => {
            if (cb !== e.target) cb.checked = false;
        });
    }
});
</script>
<?php include '/../../../include/imports.php' ?>
<?php include '/../../../include/footer.php' ?>
</body>
</html>
