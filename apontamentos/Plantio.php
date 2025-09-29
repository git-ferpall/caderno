<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Plantio</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
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
                <div class="apt-box">
                    
                    <form action="salvar.php" method="post" class="main-form" id="plantio-form">
                        <?php
                        // Campos do apontamento de Plantio
                        campo_data(1);
                        campo_area_cultivada(1);
                        campo_produto_cultivado(1);
                        campo_quantidade(1);
                        campo_previsao_colheita(1);
                        campo_obs(1);
                        ?>

                        <div class="form-submit">
                            <button class="main-btn form-cancel fundo-vermelho" type="button" onclick="window.history.back();">
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

        <?php include '../include/imports.php'; ?>
    </div>
        
    <?php include '../include/footer.php'; ?>
</body>
</html>

<?php
// 🔹 Importa as funções de campos que você já tem
require_once __DIR__ . '/../funcoes/campos_apontamento.php';
