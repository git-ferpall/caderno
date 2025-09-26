<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>
    <?php include '../funcoes/listar_maquinas.php'; ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main id="maquinas" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Relação de Máquinas</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">
                    <?php
                    if (!empty($maquinas)) {
                        foreach ($maquinas as $maquina) {
                            echo '
                                <div class="item" id="maq-' . $maquina['id'] . '">
                                    <h4 class="item-title">' . htmlspecialchars($maquina['nome']) . '</h4>
                                    <div class="item-edit">
                                        <!-- Editar -->
                                        <button class="edit-btn" type="button"
                                            onclick="editItem(this)"
                                            data-maquina=\'' . json_encode($maquina, JSON_HEX_APOS | JSON_HEX_QUOT) . '\'>
                                            <div class="edit-icon icon-pen"></div>
                                        </button>
                                        <!-- Excluir -->
                                        <button class="edit-btn" type="button" onclick="deleteMaquina(' . $maquina['id'] . ')">
                                            <div class="edit-icon icon-trash"></div>
                                        </button>
                                    </div>
                                </div>
                            ';
                        }
                    } else {
                        echo '<div class="item-none">Nenhuma máquina cadastrada.</div>';
                    }
                    ?>
                </div>

                <form action="../funcoes/salvar_maquina.php" method="POST" class="main-form container" id="add-maquina">
                    <input type="hidden" name="id" id="m-id">

                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" id="maquina-add" type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Nova Máquina</span>
                        </button>
                    </div>

                    <div class="item-add-box" id="item-add-maquina">
                        <div class="item-add-box-p">
                            <div class="form-campo">
                                <label class="item-label" for="m-nome">Nome da Máquina</label>
                                <input type="text" class="form-text" name="mnome" id="m-nome" placeholder="Ex: Trator, Pulverizador..." required>
                            </div>
                            <div class="form-campo">
                                <label class="item-label" for="m-marca">Marca ou Nome Comercial</label>
                                <input type="text" class="form-text" name="mmarca" id="m-marca" placeholder="Ex: John Deere, Valmet..." required>
                            </div>
                            <div class="form-campo">
                                <label class="item-label">Tipo de Máquina</label>
                                <div class="form-radio-box">
                                    <label class="form-radio"><input type="radio" name="mtipo" value="1" checked/> Motorizado</label>
                                    <label class="form-radio"><input type="radio" name="mtipo" value="2"/> Acoplado</label>
                                    <label class="form-radio"><input type="radio" name="mtipo" value="3"/> Manual</label>
                                </div>
                            </div>
                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-maquina" type="button">
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde form-save" id="form-save-maquina" type="button">
                                    <span class="main-btn-text">Salvar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>
    <?php include '../include/footer.php' ?>
    <script src="../js/maquinas.js"></script>
</body>
</html>
