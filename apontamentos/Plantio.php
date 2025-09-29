<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../apontamentos/funcoes_apontamento/campos_plantio.php';

// Recupera a propriedade ativa (exemplo: vinda da sessão/token)
$propriedade_id = $_SESSION['propriedade_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag | Plantio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main id="apontamento" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Apontamento - Plantio</h2>
            </div>

            <div class="sistema-main container">
                <div class="apt-box">
                    <form action="plantio.php" class="main-form" id="plantio-form">
                        <?php campos_plantio(1, $propriedade_id); ?>

                        <div class="form-submit">
                            <button class="main-btn form-cancel fundo-vermelho" type="button">
                                <span class="main-btn-text">Cancelar</span>
                            </button>
                            <button class="main-btn form-save fundo-verde" type="submit">
                                <span class="main-btn-text">Salvar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>

    <?php include '../include/footer.php' ?>
    <script src="../apontamentos/js_apontamento/apontamento.js"></script>
</body>
</html>
