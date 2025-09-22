<?php
ob_start();
require_once __DIR__ . '/../configuracao/session.php';
require_login();

// $usuarioId = $_SESSION['user_id'] ?? null;
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

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main id="home" class="sistema fundo-img">
            <div class="container-fluid principais-abas">
                <h3 class="main-title cor-branco">Principais abas</h3>

                <div class="item-box prop-box">

                    <?php

                    if(!empty($propriedades)){
                        // Aqui vai uma função pra pegar a propriedade selecionada atualmente
                        $propriedade = $propriedades[0]; 
                        echo '
                            <div class="item item-propriedade v2 fundo-branco" id="prop-' . $propriedade['id'] . '-home">
                                <h4 class="item-title">' . $propriedade['nome'] . '</h4>
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
                    <a href="./apontamento.php"><li class="menu-link fundo-verde">
                        <div class="btn-icon icon-plus cor-branco"></div>
                        <span class="link-title cor-branco">Novo Apontamento</span>
                    </li></a>
                    <a href="./silo.php"><li class="menu-link fundo-azul cor-branco">
                        <div class="btn-icon icon-silo"></div>
                        <span class="link-title cor-branco">Silo de Dados</span>
                    </li></a>
                    <a href="./produtos.php"><li class="menu-link">
                        <div class="btn-icon icon-fruit"></div>
                        <span class="link-title">Produtos</span>
                    </li></a>
                    <a href="./areas.php"><li class="menu-link">
                        <div class="btn-icon icon-plant"></div>
                        <span class="link-title">Áreas</span>
                    </li></a>
                    <a href="./relatorios.php"><li class="menu-link">
                        <div class="btn-icon icon-pen"></div>
                        <span class="link-title">Relatórios</span>
                    </li></a>
                </ul>
            </div>

            <div class="apontamento container-fluid">
                <div class="apontamento-collapse apontamento-fazer active">
                    <div class="apontamento-header fundo-laranja" id="manejo-fazer">
                        <div class="apontamento-title">
                            <span class="apontamento-count cor-laranja">0</span>
                            <h2 class="apontamento-title-text">Manejo a fazer</h2>
                        </div>
                        <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                    </div>

                    <div class="main-tabela">
                        <table class="apontamento-tabela">
                            <thead>
                                <tr>
                                    <th id="apt-data">Data</th>
                                    <th id="apt-nome">Apontamento</th>
                                    <th id="apt-area">Área</th>
                                    <th id="apt-cult">Cultivo</th>
                                </tr>
                            </thead>
                            <tbody><!-- Está vazio --></tbody>
                        </table>

                        <!-- Só aparece caso não haja nenhum item na tabela -->
                        <p class="nenhum-apontamento">Nenhum apontamento</p>
                    </div>
                </div>
                
                <div class="apontamento-collapse apontamento-concluido active">
                    <div class="apontamento-header fundo-azul" id="manejo-concluido">
                        <div class="apontamento-title">
                            <span class="apontamento-count cor-azul">4</span>
                            <h2 class="apontamento-title-text">Manejo concluído</h2>
                        </div>
                        <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                    </div>
                
                    <div class="main-tabela">
                        <table class="apontamento-tabela">
                            <thead>
                                <tr>
                                    <th id="apt-data">Data</th>
                                    <th id="apt-nome">Apontamento</th>
                                    <th id="apt-area">Área</th>
                                    <th id="apt-cult">Cultivo</th>
                                </tr>
                            </thead>
                            <tbody> 
                                <tr class="espaco-tr"><td colspan="4"></td></tr>
                                <!-- 
                                # Exemplo: A estrutura básica é assim, com o último filho (o Cultivo) tendo um fundo de cor de acordo com o produto cadastrado, cor essa que o usuário escolhe na hora do seu efetivo cadastro
                                <tr>
                                    <td>19/05/2025</td>
                                    <td>Aplicação de calcário</td>
                                    <td>Talhão 1</td>
                                    <td><div class="apontamento-produto fundo-laranja">Milho</div></td>
                                </tr>
                                -->
                            </tbody>
                        </table>

                        <!-- Só aparece caso não haja nenhum item na tabela -->
                        <p class="nenhum-apontamento">Nenhum apontamento</p>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>
    
    <?php include '../include/footer.php' ?>
<?php ob_end_flush(); ?>
</body>
</html>