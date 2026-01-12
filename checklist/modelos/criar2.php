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

/* 🔒 BASE DO SISTEMA (CONFIRMADA) */
define('APP_PATH', realpath(__DIR__ . '/../../'));

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
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>
    <link rel="icon" type="image/png" href="/../../img/logo-icon.png">
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
        <main id="propriedade" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">✏️ <?= $id ? 'Editar' : 'Criar' ?> modelo de checklist</h2>
            </div>
                <form action="salvar.php" method="POST" class="main-form container" id="prop-form">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-campo">
                        <label for="titulo">Título</label>
                        <input class="form-text" type="text" name="titulo" id="titulo"
                            placeholder="Título do Checklist" required
                            value="<?= htmlspecialchars($modelo['titulo'] ?? '') ?>">
                    </div>
                    <div class="form-campo">
                        <label for="descricao">Descrição</label>
                        <textarea
                            name="descricao"
                            id="descricao"
                            class="form-text"
                            rows="3"
                            placeholder="Descreva aqui..."
                        ><?= htmlspecialchars($modelo['descricao'] ?? '') ?></textarea>
                    </div>
        

                    <div class="form-box">
                        <div class="form-campo f2">
                             <input type="checkbox" name="publico" class="form-check-input"
                                <?= $modelo['publico'] ? 'checked' : '' ?>>
                            <label class="form-check-label">Modelo padrão do sistema</label>
                        </div>

                    </div>
                    <h3>📋 Itens do checklist</h3>

                        <div id="itens">
                        <?php foreach ($itens as $i):
                            $key = 'id_' . $i['id'];
                        ?>
                            <div class="form-campo item" data-key="<?= $key ?>">

                                <!-- Ordem / mover -->
                                <div class="form-box">
                                    <span class="handle" title="Mover item">☰</span>

                                    <input type="hidden" name="item_key[]" value="<?= $key ?>">

                                    <!-- Descrição -->
                                    <input
                                        type="text"
                                        name="item_desc[<?= $key ?>]"
                                        class="form-text"
                                        placeholder="Descrição do item"
                                        value="<?= htmlspecialchars($i['descricao']) ?>"
                                        required
                                    >

                                    <!-- Opções -->
                                    <div class="form-opcoes">

                                        <label class="form-check">
                                            <input class="form-check-input opcao-item"
                                                type="checkbox"
                                                name="item_obs[<?= $key ?>]"
                                                value="1"
                                                <?= $i['permite_observacao'] ? 'checked' : '' ?>>
                                            <span>Obs</span>
                                        </label>

                                        <label class="form-check">
                                            <input class="form-check-input opcao-item"
                                                type="checkbox"
                                                name="item_foto[<?= $key ?>]"
                                                value="1"
                                                <?= !empty($i['permite_foto']) ? 'checked' : '' ?>>
                                            <span>Foto</span>
                                        </label>

                                        <label class="form-check">
                                            <input class="form-check-input opcao-item"
                                                type="checkbox"
                                                name="item_anexo[<?= $key ?>]"
                                                value="1"
                                                <?= !empty($i['permite_anexo']) ? 'checked' : '' ?>>
                                            <span>Doc</span>
                                        </label>

                                    </div>

                                    <!-- Remover -->
                                    <button type="button"
                                            class="main-btn fundo-vermelho btn-remover"
                                            onclick="this.closest('.item').remove()">
                                        ×
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                        </div>

                </form>

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

</body>
</html>