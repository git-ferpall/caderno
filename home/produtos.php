<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../configuracao/configuracao_funcoes.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';



if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    header("Location: ../index.php");
    exit();
}


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
        .edit-btn {
            background-color: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-icon {
            font-size: 18px;
            color: #333;
            line-height: 1;
        }

        .edit-icon.icon-pen::before {
            content: "✏️"; /* Ícone de lápis */
            display: inline-block;
        }

        .edit-btn:hover .edit-icon {
            color: #007f00;
            transform: scale(1.1);
            transition: all 0.2s ease;
        }
        .edit-icon.icon-trash::before {
            content: "🗑️";
            display: inline-block;
        }
        .item-edit {
        display: flex;
        gap: 6px; /* Espaço entre os ícones */
        justify-content: center;
        align-items: center;
    }
        .item > div {
        text-align: center;
        vertical-align: middle;
    }
    .item-edit {
        display: flex;
        justify-content: center;
        gap: 5px;
    }
    .item-header, .item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 10px;
    background-color: #f6f6f6;
    margin-bottom: 4px;
    }

    .col-nome        { width: 25%; text-align: center; }
    .col-tipo        { width: 20%; text-align: center; }
    .col-marca       { width: 20%; text-align: center; }
    .col-propriedade { width: 25%; text-align: center; }
    .item-edit       { width: 10%; display: flex; justify-content: center; gap: 8px; }

    .item-none {
        padding: 10px;
        text-align: center;
        color: #777;
    }

</style>

</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>
        <main id="produtos" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Produtos Cultivados</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box" id="tabela-produtos">
                    <div class="item item-header">
                        <div class="col-nome"><b><span style="font-size: 20px;">Produto</span></b></div>
                        <div class="col-tipo"><b><span style="font-size: 20px;">Cultivo</span></b></div>
                        <div class="col-marca"><b><span style="font-size: 20px;">Atributo</span></b></div>
                        <div class="col-propriedade"><b><span style="font-size: 20px;">Propriedade</span></b></div>
                        <div class="item-edit"></div>
                    </div>
                </div>

                <form action="produtos.php" class="main-form" id="add-produto">

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
                                <button class="item-btn fundo-cinza-b cor-preto" id="form-cancel" type="button">
                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde" id="form-save" type="button">
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
    <script src="../js/produtos.js"></script>
    
    <?php include '../include/footer.php' ?>
</body>
</html>