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
   /* ============================= */
    /* FUNDO COM IMAGEM */
    /* ============================= */

    .fundo-img {
        background-image: url("../img/bg-sistema.jpg"); /* ajuste se necessário */
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }


    /* ============================= */
    /* WRAPPER CENTRALIZADO */
    /* ============================= */

    .relatorios-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 30px 20px 40px;
    }

    /* ============================= */
    /* GRID RESPONSIVO */
    /* ============================= */

    .relatorios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
    }

    /* ============================= */
    /* CARD RELATÓRIO */
    /* ============================= */

    .card-relatorio {
        background: #ffffff;
        padding: 30px;
        border-radius: 18px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        text-decoration: none;
        color: #333;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .card-relatorio:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 40px rgba(0,0,0,0.15);
    }

    /* HEADER DO CARD */

    .card-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-icon {
        font-size: 26px;
    }

    .card-relatorio h3 {
        margin: 0;
        font-size: 20px;
    }

    .card-relatorio p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
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