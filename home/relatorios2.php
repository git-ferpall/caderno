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
<style>
   
</style>    
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?> 
    <div id="conteudo">
        <?php include '../include/menu.php' ?>



        <main id="relatorios" class="sistema fundo-img">

            <div class="overlay-conteudo">

                <div class="page-title">
                    <h2 class="main-title cor-branco">Central de Relatórios</h2>
                </div>

                <div class="sistema-main">
                    <div class="relatorios-wrapper">

                        <div class="relatorios-grid">

                            <a href="relatorio_manejos.php" class="card-relatorio">
                                <div class="card-header">
                                    <span class="card-icon">📊</span>
                                    <h3>Relatório de Manejos</h3>
                                </div>
                                <p>Aplicações, defensivos e operações realizadas.</p>
                            </a>

                            <a href="relatorio_visita.php" class="card-relatorio">
                                <div class="card-header">
                                    <span class="card-icon">🧑‍🌾</span>
                                    <h3>Relatório de Visita Técnica</h3>
                                </div>
                                <p>Registros de visitas e recomendações técnicas.</p>
                            </a>

                            <a href="relatorio_producao.php" class="card-relatorio">
                                <div class="card-header">
                                    <span class="card-icon">📦</span>
                                    <h3>Relatório de Produção</h3>
                                </div>
                                <p>Resumo de colheita e produtividade.</p>
                            </a>

                        </div>

                    </div>
                </div>

            </div>

        </main>

        <?php include '../include/imports.php' ?>
        
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        
    <?php include '../include/footer.php' ?>
</body>
</html>