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
    <?php require '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <div id="conteudo" class="home-layout">
        <?php include '../include/menu.php' ?>

        <main id="home" class="sistema fundo-img">
            <div class="container-fluid principais-abas">
                <h3 class="main-title cor-branco"></h3>

               <div class="item-box prop-box">
                    <?php
                    if (!empty($propriedades)) {
                        // Pega a propriedade ativa (ou a primeira retornada)
                        $propriedade = $propriedades[0]; 
                        echo '
                            <div class="item item-propriedade v2 fundo-branco" id="prop-' . (int)$propriedade['id'] . '-home">
                                <h4 class="item-title">' . htmlspecialchars($propriedade['nome_razao']) . '</h4>
                                <div class="item-edit">
                                    <button class="edit-btn" id="edit-propriedade-home" type="button" onclick="altProp()">
                                        Alterar
                                    </button>
                                </div>
                            </div>
                        ';
                    } else {
                        echo '<div class="item-none">Nenhuma propriedade cadastrada.</div>';
                    }
                    ?>
                </div>


                <ul class="menu-links">
                    <a href="./apontamento"><li class="menu-link fundo-verde">
                        <div class="btn-icon icon-plus cor-branco"></div>
                        <span class="link-title cor-branco">Novo Apontamento</span>
                    </li></a>
                    <a href="./silo"><li class="menu-link fundo-azul cor-branco">
                        <div class="btn-icon icon-silo"></div>
                        <span class="link-title cor-branco">Silo de Dados</span>
                    </li></a>
                    <a href="./produtos"><li class="menu-link">
                        <div class="btn-icon icon-fruit"></div>
                        <span class="link-title">Produtos</span>
                    </li></a>
                    <a href="./areas"><li class="menu-link">
                        <div class="btn-icon icon-plant"></div>
                        <span class="link-title">Áreas</span>
                    </li></a>
                    <a href="./relatorios"><li class="menu-link">
                        <div class="btn-icon icon-pen"></div>
                        <span class="link-title">Relatórios</span>
                    </li></a>
                </ul>
            </div>

            <div class="apontamento container">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="apontamento-collapse apontamento-fazer manejos-panel active">
                            <div class="apontamento-header" id="manejo-fazer">
                                <div class="apontamento-title">
                                    <span class="apontamento-count cor-laranja">0</span>
                                    <h2 class="apontamento-title-text">Manejo a fazer</h2>
                                </div>
                                <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                            </div>

                            <div class="main-tabela">
                                <div class="manejos-table-wrap">
                                    <table class="apontamento-tabela">
                                        <thead>
                                            <tr>
                                                <th id="apt-data">Data</th>
                                                <th id="apt-nome">Apontamento</th>
                                                <th id="apt-area">Área</th>
                                                <th id="apt-cult">Cultivo</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="manejos-pagination" data-status="pendente">
                                    <button type="button" class="manejos-page-btn" data-status="pendente" data-dir="-1" aria-label="Página anterior">&lt;</button>
                                    <span class="manejos-page-text" data-status="pendente">Página 1</span>
                                    <button type="button" class="manejos-page-btn" data-status="pendente" data-dir="1" aria-label="Próxima página">&gt;</button>
                                </div>

                                <p class="nenhum-apontamento">Nenhum apontamento pendente</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-6">
                        <div class="apontamento-collapse apontamento-concluido manejos-panel active">
                            <div class="apontamento-header" id="manejo-concluido">
                                <div class="apontamento-title">
                                    <span class="apontamento-count cor-azul">0</span>
                                    <h2 class="apontamento-title-text">Manejo concluído</h2>
                                </div>
                                <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                            </div>
                        
                            <div class="main-tabela">
                                <div class="manejos-table-wrap">
                                    <table class="apontamento-tabela">
                                        <thead>
                                            <tr>
                                                <th id="apt-data">Data</th>
                                                <th id="apt-conclusao">Conclusão</th>
                                                <th id="apt-nome">Apontamento</th>
                                                <th id="apt-area">Área</th>
                                                <th id="apt-cult">Cultivo</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="manejos-pagination" data-status="concluido">
                                    <button type="button" class="manejos-page-btn" data-status="concluido" data-dir="-1" aria-label="Página anterior">&lt;</button>
                                    <span class="manejos-page-text" data-status="concluido">Página 1</span>
                                    <button type="button" class="manejos-page-btn" data-status="concluido" data-dir="1" aria-label="Próxima página">&gt;</button>
                                </div>

                                <p class="nenhum-apontamento">Nenhum apontamento concluído</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/home_manejos.js"></script>
        <script src="../js/home_manejos_popup.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>