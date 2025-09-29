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

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <?php 

        // Aqui vai uma função pra pegar as áreas já cadastradas que, caso possua alguma, o valor já é colocado automaticamente no campo passível de edição

        // Exemplo de area:
        // $areas = [['id' => '01', 'nome' => 'Area 01']]

        $areas = []
        
        ?>

        <main id="areas" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Áreas Cultivadas</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">

                    <?php

                    if(!empty($areas)){
                        foreach($areas as $area) {
                            echo '
                                <div class="item" id="area-' . $area['id'] . '">
                                    <h4 class="item-title">' . $area['nome'] . '</h4>
                                    <div class="item-edit">
                                        <button class="edit-btn" id="edit-area" type="button" onclick="editItem(' . json_encode($area) . ')">
                                            <div class="edit-icon icon-pen"></div>
                                        </button>
                                    </div>
                                </div>
                            ';
                        }
                    } else {
                        echo '<div class="item-none">Nenhuma área cadastrada.</div>';
                    }

                    ?>
                </div>

                <form action="../funcoes/salvar_area.php" class="main-form container" id="add-area">

                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" id="area-add" type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Nova área</span>
                        </button>
                    </div>

                    <div class="item-add-box" id="item-add-area">
                        <div class="item-add-box-p">
                            <div class="form-campo">
                                <label class="item-label" for="a-nome">Nome da área</label>
                                <input type="text" class="form-text" name="anome" id="a-nome" placeholder="Ex: Estufa 1, Horta Cima, Terreno..." required>
                            </div>

                            <div class="form-campo">
                                <label class="item-label" for="a-tipo">Tipo de Área</label>
                                <div class="form-radio-box" id="a-tipo">
                                    <label class="form-radio">
                                        <input type="radio" name="atipo" value="1" checked/>
                                        Estufa
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="atipo" value="2" />
                                        Solo
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="atipo" value="3" />
                                        Outro
                                    </label>
                                </div>
                            </div>

                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-area" type="button">
                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde form-save" id="form-save-area" type="button">
                                    <!-- <div class="btn-icon icon-check cor-verde"></div> -->
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
</body>
</html>