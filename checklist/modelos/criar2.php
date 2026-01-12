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

                    <div class="form-campo">
                        <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control"
                                value="<?= htmlspecialchars($modelo['titulo'] ?? '') ?>" required>
                    </div>

                    <div class="form-campo">
                        <label for="pf-cnpj-cpf">Tipo e N° do Documento</label>
                        <div class="form-box" id="pf-cnpj-cpf">
                            <select name="pftipo" id="pf-tipo" class="form-select form-text f1" required>
                                <option value="cnpj" <?php echo ($cnpj !== '') ? 'selected' : ''; ?>>CNPJ</option>
                                <option value="cpf"  <?php echo ($cpf !== '')  ? 'selected' : ''; ?>>CPF</option>
                            </select>

                            <input class="form-text only-num f4" type="text" name="pfcnpj" id="pf-cnpj" 
                                placeholder="12.345.789/0001-10" maxlength="18" 
                                value="<?php echo htmlspecialchars($cnpj); ?>">
                            <input class="form-text only-num f4" type="text" name="pfcpf" id="pf-cpf" 
                                placeholder="123.456.789-10" maxlength="14" 
                                value="<?php echo htmlspecialchars($cpf); ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-email-com">E-mail</label>
                        <input class="form-text" type="email" name="pfemail-com" id="pf-email-com"
                            placeholder="Seu e-mail comercial" required
                            value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                        
                    <div class="form-box">
                        <div class="form-campo f5">
                            <label for="pf-ender-rua">Endereço</label>
                            <input class="form-text" type="text" name="pfender-rua" id="pf-ender-rua" 
                                placeholder="Rua, logradouro, etc" required
                                value="<?php echo htmlspecialchars($ruaEnder); ?>">
                        </div>
                        <div class="form-campo f2">
                            <label for="pf-ender-num">N°</label>
                            <input type="text" class="form-text form-num only-num" 
                                name="pfender-num" id="pf-ender-num" placeholder="S/N" maxlength="6" 
                                value="<?php echo htmlspecialchars($numEnder); ?>">
                        </div>
                    </div>

                    <div class="form-box">
                        <div class="form-campo f2">
                            <label for="pf-ender-uf">Estado</label>
                            <select name="pfender-uf" id="pf-ender-uf" class="form-select form-text" value="<?php echo $ufEnder ?>" required></select>
                        </div>
                        <div class="form-campo f5">
                            <label for="pf-ender-cid">Cidade</label>
                            <select name="pfender-cid" id="pf-ender-cid" class="form-select form-text" value="<?php echo $cidEnder ?>" required></select>
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num1-com">Telefone Comercial</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum1-com" id="pf-num1-com"
                                placeholder="(DDD) + Número" maxlength="15" 
                                value="<?php echo htmlspecialchars($telCom); ?>">
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="pf-num2-com">Telefone Comercial Secundário</label>
                        <div class="form-box">
                            <input class="form-text form-tel only-num" type="tel" name="pfnum2-com" id="pf-num2-com"
                                placeholder="(DDD) + Número" maxlength="15" 
                                value="<?php echo htmlspecialchars($telCom2); ?>">
                        </div>
                    </div>

                    <div class="form-submit">
                        <button class="main-btn fundo-vermelho form-cancel" id="form-cancel-propriedade" type="button">
                            <span class="main-btn-text">Cancelar</span>
                        </button>
                        <button class="main-btn fundo-verde form-save" id="form-save-propriedade" type="submit">
                            <span class="main-btn-text">Salvar</span>
                        </button>
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