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

        <main id="hidroponia" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Hidroponia</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">

                <?php
                ?>
                <div id="lista-estufas"></div>
                <!-- Aqui vai uma função pra pegar as estufas cadastradas, ou importar de um arquivo json
                 $estufas = [
                    ['id' => 1, 'nome' => 'Estufa 01', 'area' => '', 'obs' => 'Exemplo de observação',
                        'bancadas' => []
                    ],
                    ['id' => 2, 'nome' => 'Estufa 02', 'area' => '', 'obs' => '',
                        'bancadas' => [
                            ['nome' => '01', 'cultura' => '', 'obs' => ''],
                            ['nome' => '02', 'cultura' => '', 'obs' => ''],
                            ['nome' => '03', 'cultura' => '', 'obs' => ''],
                            ['nome' => '04', 'cultura' => '', 'obs' => '']
                        ]
                    ],
                    ['id' => 3, 'nome' => 'Estufa 03', 'area' => '', 'obs' => '',
                        'bancadas' => [
                            ['nome' => '01', 'cultura' => '', 'obs' => ''],
                            ['nome' => '02', 'cultura' => '', 'obs' => 'Exemplo de observação']
                        ],
                    ]
                ]; -->
                <?php
                ?>
                <div id="lista-estufas"></div>

                
                    
                </div>

                <form action="hidroponia.php" class="main-form container" id="add-estufa">

                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" id="estufa-add" type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Nova Estufa</span>
                        </button>
                    </div>

                    <div class="item-add-box" id="item-add-estufa">
                        <div class="item-add-box-p">
                            <div class="form-campo">
                                <label class="item-label" for="e-nome">Nome da Estufa</label>
                                <input type="text" class="form-text" name="enome" id="e-nome" placeholder="Ex: Estufa 01, Estufa 02..." required>
                            </div>

                            <div class="form-campo">
                                <label class="item-label" for="e-area">Área (m²)</label>
                                <input type="text" class="form-text" name="earea" id="e-area" placeholder="Área em m² (Opcional)">
                            </div>

                            <div class="form-campo">
                                <label for="e-obs">Observações</label>
                                <textarea class="form-text form-textarea" name="eobs" id="e-obs" placeholder="Insira aqui suas observações..."></textarea>
                            </div>

                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-estufa" type="button">
                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde form-save" id="form-save-estufa" type="button">
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
    <script type="module" src="../js/hidroponia.js"></script>

    <?php include '../include/footer.php' ?>
</body>
</html>