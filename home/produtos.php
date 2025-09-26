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
    <?php include '../funcoes/listar_produtos.php'; ?>


    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <?php 

        // Aqui vai uma função pra pegar os produtos já cadastrados que, caso possua algum, o valor já é colocado automaticamente no campo passível de edição

        // Exemplo de produto:
        // $produtos = [['id' => '01', 'nome' => 'Maçã']]

        
        
        ?>

        <main id="produtos" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Produtos Cultivados</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">
                <input type="hidden" name="id" id="p-id">


                    <?php

                    if (!empty($produtos)) {
                        foreach ($produtos as $produto) {
                            echo '
                                <div class="item" id="prod-' . $produto['id'] . '">
                                    <h4 class="item-title">' . htmlspecialchars($produto['nome']) . '</h4>
                                    <div class="item-edit">
                                        <!-- Botão Editar -->
                                        <button class="edit-btn" type="button"
                                            onclick="editItem(this)"
                                            data-produto=\'' . json_encode($produto, JSON_HEX_APOS | JSON_HEX_QUOT) . '\'>
                                            <div class="edit-icon icon-pen"></div>
                                        </button>

                                        <!-- Botão Excluir -->
                                        <button class="edit-btn" type="button" onclick="deleteProduto(' . $produto['id'] . ')">
                                            <div class="edit-icon icon-trash"></div>
                                        </button>
                                    </div>
                                </div>
                            ';
                        }
                    } else {
                        echo '<div class="item-none">Nenhum produto cadastrado.</div>';
                    }


                    ?>
                </div>

                <form action="../funcoes/salvar_produto.php" method="POST" class="main-form container" id="add-produto">
                    <input type="hidden" name="id" id="p-id">

                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" id="produto-add" type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Novo Produto</span>
                        </button>
                    </div>

                    <div class="item-add-box" id="item-add-produto">
                        <div class="item-add-box-p">
                            <div class="form-campo">
                                <label class="item-label" for="p-nome">Nome do Produto</label>
                                <input type="text" class="form-text" name="pnome" id="p-nome" placeholder="Ex: Morango, Alface, Ameixa..." required>
                            </div>

                            <div class="form-campo">
                                <label class="item-label" for="p-tipo">Tipo de Cultivo</label>
                                <div class="form-radio-box" id="p-tipo">
                                    <label class="form-radio">
                                        <input type="radio" name="ptipo" value="1" checked/>
                                        Convencional
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="ptipo" value="2" />
                                        Orgânico
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="ptipo" value="3" />
                                        Integrado
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-campo">
                                <label class="item-label" for="p-tipo">Outros Atributos</label>
                                <div class="form-radio-box"  id="p-atr">
                                    <label class="form-radio">
                                        <input type="radio" name="patr" value="hidro" checked/>
                                        Hidropônico
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="patr" value="semi-hidro" />
                                        Semi-Hidropônico
                                    </label>
                                    <label class="form-radio">
                                        <input type="radio" name="patr" value="solo" />
                                        Solo
                                    </label>
                                </div>
                            </div>

                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-produto" type="button">
                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde form-save" id="form-save-produto" type="button">
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
    <script src="../js/produtos.js"></script>
</body>
</html>