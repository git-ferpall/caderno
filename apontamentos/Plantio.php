<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/funcoes_apontamento/campos_plantio.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Plantio</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php require '../include/loading.php'; ?> 
    <?php include '../include/popups.php'; ?>
    <div id="conteudo">
        <?php include '../include/menu.php'; ?>

        <main id="apontamento" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Apontamento - Plantio</h2>
            </div>

            <div class="sistema-main container">
                <form action="salvar.php" method="post" class="main-form" id="plantio-form">
                    <?php campos_plantio(1); ?>

                    <div class="form-submit">
                        <button class="main-btn form-cancel fundo-vermelho" type="button">Cancelar</button>
                        <button class="main-btn form-save fundo-verde" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
        </main>
        <?php include '../include/footer.php'; ?>
    </div>
</body>
</html>
