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
   .relatorios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
    }

    .card-relatorio {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transition: 0.2s;
        text-decoration: none;
        color: #333;
    }

    .card-relatorio:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
</style>    
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?> 
    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <?php
        date_default_timezone_set("America/Sao_Paulo");

        // Aqui vai uma função pra pegar as informações do sistema que, caso possua algum dado cadastrado, esse valor já é colocado automaticamente no campo passível de edição

        $cultivos = [];
        $areas = [];
        $manejos = [];

        $dt_ini = date("Y-m-01");
        $dt_fin = date("Y-m-t");
        ?>

        <main id="relatorios" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Relatórios</h2>
            </div>

            <main class="sistema">
                <div class="page-title">
                    <h2 class="main-title cor-branco">Central de Relatórios</h2>
                </div>

                <div class="sistema-main relatorios-grid">

                    <a href="relatorio_manejos.php" class="card-relatorio">
                        <h3>📊 Relatório de Manejos</h3>
                        <p>Aplicações, defensivos e operações realizadas.</p>
                    </a>

                    <a href="relatorio_visita.php" class="card-relatorio">
                        <h3>🧑‍🌾 Relatório de Visita Técnica</h3>
                        <p>Registros de visitas e recomendações técnicas.</p>
                    </a>

                    <a href="relatorio_producao.php" class="card-relatorio">
                        <h3>📦 Relatório de Produção</h3>
                        <p>Resumo de colheita e produtividade.</p>
                    </a>

                </div>
            </main>

        <?php include '../include/imports.php' ?>
        
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        
    <?php include '../include/footer.php' ?>
</body>
</html>