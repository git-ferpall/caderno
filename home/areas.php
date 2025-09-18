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
                /* Estilo para botões de editar/excluir */
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
            content: "✏️";
            display: inline-block;
        }

        .edit-icon.icon-trash::before {
            content: "🗑️";
            display: inline-block;
        }

        .edit-btn:hover .edit-icon {
            color: #007f00;
            transform: scale(1.1);
            transition: all 0.2s ease;
        }

        /* Estilo das colunas da tabela */
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
        .col-propriedade { width: 25%; text-align: center; }
        .item-edit       { width: 10%; display: flex; justify-content: center; gap: 8px; }

        .item-none {
            padding: 10px;
            text-align: center;
            color: #777;
        }
        #item-add-area {
            display: none;
        }

    </style>    
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <div id="conteudo">
        <?php include '../include/menu.php' ?>       
        <main id="areas" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Áreas Cultivadas</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box" id="tabela-areas">
                    
                    <!-- Conteúdo carregado dinamicamente via busca_areas.php -->
                </div>

                <form action="areas.php" class="main-form" id="add-area">
                    <input type="hidden" name="area_id" id="area-id" />

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
                                <button class="item-btn fundo-cinza-b cor-preto" id="form-cancel" type="button">
                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde" id="form-save" type="button">
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
    <script src="/js/areas.js"></script>
</body>
</html>