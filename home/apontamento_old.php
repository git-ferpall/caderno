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
    
    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <main id="apontamento" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Novo Apontamento</h2>
            </div>

            <div class="sistema-main">
                <div class="apt-box popup-overflow">
                    
                    <?php 
                    $apts = [
                        ['id' => 1, 'nome' => 'Plantio'],
                        ['id' => 2, 'nome' => 'Transplantio'],
                        ['id' => 3, 'nome' => 'Colheita'],
                        ['id' => 4, 'nome' => 'Registros Climáticos'],
                        ['id' => 5, 'nome' => 'Fertilizante'],
                        ['id' => 6, 'nome' => 'Herbicida'],
                        ['id' => 7, 'nome' => 'Fungicida'],
                        ['id' => 8, 'nome' => 'Inseticida'],
                        ['id' => 9, 'nome' => 'Adubação (Calcário)'],
                        ['id' => 10, 'nome' => 'Adubação Orgânica'],
                        ['id' => 11, 'nome' => 'Irrigação'],
                        ['id' => 12, 'nome' => 'Controle de Água'],
                        ['id' => 13, 'nome' => 'Mosca-das-frutas'],
                        ['id' => 14, 'nome' => 'Pragas e Doenças'],
                        ['id' => 15, 'nome' => 'Manejo Integrado'],
                        ['id' => 16, 'nome' => 'Erradicação'],
                        ['id' => 17, 'nome' => 'Revisão de Máquinas'],
                        ['id' => 18, 'nome' => 'Coleta e Análise'],
                        ['id' => 19, 'nome' => 'Visita Técnica'],
                        ['id' => 20, 'nome' => 'Personalizado']
                    ];

                    // Adiciona os ícones dos apontamentos
                    foreach ($apts as $apt) {
                        $id = $apt['id'];
                        $nome = $apt['nome'];
                        $aptJson = htmlspecialchars(json_encode($apt), ENT_QUOTES, 'UTF-8');

                        echo '<button class="apt-button fundo-apt' . $id . '" type="button" id="apt' . $id . '" onclick="novoApontamento(' . $aptJson . ')">
                            <div class="apt-icon-box">
                                <div class="apt-icon icon-apt' . $id . ' cor-apt' . $id . '"></div>
                            </div>
                            <h5 class="apt-title">' . htmlspecialchars($nome) . '</h5>
                        </button>';
                    }

                    // Adiciona os formulários de adição de cada apontamento
                    foreach ($apts as $apt) {
                        $id = $apt['id'];

                        echo '<div class="apt-add d-none" id="add-apt'. $id .'"> <form action="apontamento.php" class="main-form" id="apt'. $id .'-form">';

                        if ($id == 1) {
                            // Apontamento 1 - Plantio
                            echo campo_data($id);
                            echo campo_area_cultivada($id); 
                            echo campo_produto_cultivado($id);
                            echo campo_quantidade($id);
                            echo campo_previsao_colheita($id);
                            echo campo_obs($id);

                        } else if ($id == 2) {
                            // Apontamento 2 - Transplantio
                            echo campo_data($id);
                            echo campo_produto_cultivado($id);
                            echo campo_area_origem($id);
                            echo campo_area_destino($id);
                            echo campo_quantidade($id);
                            echo campo_obs($id);                            

                        } else if ($id == 3) {
                            // Apontamento
                            
                        } else if ($id == 4) {
                            // Apontamento
                            
                        } else if ($id == 5) {
                            // Apontamento 5 - Fertilizante
                            echo campo_data($id);
                            echo campo_area_cultivada($id);
                            echo campo_produto_cultivado($id);
                            echo campo_tipo_fertilizante($id);
                            echo campo_produto_utilizado($id);
                            echo campo_forma_aplicacao($id);
                            echo campo_quantidade_kg($id);
                            echo campo_n_referencia_amostra($id);
                            echo campo_obs($id);
                            
                        } else if ($id == 6) {
                            // Apontamento
                            
                        } else if ($id == 7) {
                            // Apontamento
                            
                        } else if ($id == 8) {
                            // Apontamento
                            
                        } else if ($id == 9) {
                            // Apontamento
                            
                        } else if ($id == 10) {
                            // Apontamento
                            
                        } else if ($id == 11) {
                            // Apontamento
                            
                        } else if ($id == 12) {
                            // Apontamento
                            
                        } else if ($id == 13) {
                            // Apontamento
                            
                        } else if ($id == 14) {
                            // Apontamento
                            
                        } else if ($id == 15) {
                            // Apontamento
                            
                        } else if ($id == 16) {
                            // Apontamento
                            
                        } else if ($id == 17) {
                            // Apontamento
                            
                        } else if ($id == 18) {
                            // Apontamento
                            
                        } else if ($id == 19) {
                            // Apontamento
                            
                        } else if ($id == 20) {
                            // Apontamento
                            
                        } else {

                        }

                        echo '<div class="form-submit">
                            <button class="main-btn fundo-vermelho" id="form-cancel" type="button">
                                <span class="main-btn-text">Cancelar</span>
                            </button>
                            <button class="main-btn fundo-verde" id="form-save" type="button">
                                <span class="main-btn-text">Salvar</span>
                            </button>
                        </div>';

                        echo '</form> </div>';
                    }
                    ?>
                    
                    <!-- Início das funções de adição de campo -->

                    <?php
                    // Adiciona o campo "Data"
                    function campo_data($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-data">Data</label>
                            <input class="form-text only-num" type="date" name="apt'. $id .'data" id="apt'. $id .'-data"  required>
                        </div>';
                    }

                    // Adiciona o campo "Área cultivada"
                    function campo_area_cultivada($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-area">Área cultivada</label>
                            <div class="form-box form-box-area">
                                <select name="apt'. $id .'area" id="apt'. $id .'-area" class="form-select form-text" required>
                                    <option value="-">Selecione a área cultivada</option>
                                </select>
                                <button class="add-btn add-area" type="button">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Área de origem"
                    function campo_area_origem($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-area-o">Área de origem</label>
                            <div class="form-box form-box-area">
                                <select name="apt'. $id .'area-o" id="apt'. $id .'-area-o" class="form-select form-text" required>
                                    <option value="-">Selecione a área de origem</option>
                                </select>
                                <button class="add-btn add-area" type="button" id="add-area-o">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Área de destino"
                    function campo_area_destino($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-area-d">Área de destino</label>
                            <div class="form-box form-box-area">
                                <select name="apt'. $id .'area-d" id="apt'. $id .'-area-d" class="form-select form-text" required>
                                    <option value="-">Selecione a área de destino</option>
                                </select>
                                <button class="add-btn add-area" type="button">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Produto cultivado"
                    function campo_produto_cultivado($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-produto">Produto cultivado</label>
                            <div class="form-box form-box-produto">
                                <select name="apt'. $id .'produto" id="apt'. $id .'-produto" class="form-select form-text" required>
                                    <option value="-">Selecione o produto cultivado</option>
                                </select>
                                <button class="add-btn add-produto" type="button">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade"
                    function campo_quantidade($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-qtd">Quantidade</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd" id="apt'. $id .'-qtd" placeholder="Insira a quantidade em número" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade (Kg/ha/mês)"
                    function campo_quantidade_kg($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-qtd-kg">Quantidade (Kg/ha/mês)</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd-kg" id="apt'. $id .'-qtd-kg" placeholder="Insira a quantidade em número" required>
                        </div>';
                    }

                    // Adiciona o campo "N° de referência da amostra"
                    function campo_n_referencia_amostra($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-n-ref-am">N° de referência da amostra</label>
                            <input type="text" class="form-text" name="apt'. $id .'n-ref-am" id="apt'. $id .'-n-ref-am" placeholder="Insira a quantidade em número" required>
                        </div>';
                    }

                    // Adiciona o campo "Previsão de colheita (dias)"
                    function campo_previsao_colheita($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-prev">Previsão de colheita (dias)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'prev" id="apt'. $id .'-prev" placeholder="Insira a previsão em dias" required>
                        </div>';
                    }

                    // Adiciona o campo "Observações"
                    function campo_obs($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-obs">Observações</label>
                            <textarea class="form-text form-textarea" name="apt1obs" id="apt1-obs" placeholder="Insira aqui suas observações..."></textarea>
                        </div>';
                    }

                    // Adiciona o campo "Produto utilizado (nome comercial)"
                    function campo_produto_utilizado($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-produto-utilizado">Produto utilizado (nome comercial)</label>
                            <input type="text" class="form-text" name="apt'. $id .'produto-utilizado" id="apt'. $id .'-produto-utilizado" placeholder="Insira o nome do produto utilizado" required>
                        </div>';
                    }

                    // Adiciona o campo "Produção (kg ou un / talhão)"
                    function campo_producao($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-producao">Produção (kg ou un / talhão)</label>
                            <input type="text" class="form-text" name="apt'. $id .'producao" id="apt'. $id .'-producao" placeholder="Insira a produção" required>
                        </div>';
                    }

                    // Adiciona o campo "N° do romaneio"
                    function campo_n_romaneio($id) {
                        return '<div class="form-campo">
                            <label for="apt'. $id .'-n-romaneio">N° do romaneio</label>
                            <input type="text" class="form-text" name="apt'. $id .'n-romaneio" id="apt'. $id .'-n-romaneio" placeholder="Insira o número do romaneio" required>
                        </div>';
                    }

                    // Adiciona o campo "Tipo de fertilizante"
                    function campo_tipo_fertilizante($id) {
                        return '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-tipo-fert">Tipo de fertilizante</label>
                            <div class="form-radio-box" id="apt'. $id .'-tipo-fert">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-fert" value="1" checked/>
                                    Simples (Super Simples, Cloreto de Potássio, Sulfato de Amônio, etc)
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-fert" value="2" />
                                    Formulado (NPK)
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-fert" value="3" />
                                    Líquido
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Forma de aplicação"
                    function campo_forma_aplicacao($id) {
                        return '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-forma-apl">Forma de aplicação</label>
                            <div class="form-radio-box" id="apt'. $id .'-forma-apl">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'forma-apl" value="1" checked/>
                                    Cobertura
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'forma-apl" value="2" />
                                    Incorporado
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'forma-apl" value="3" />
                                    Pulverização
                                </label>
                            </div>
                        </div>';
                    }
                    ?>

                    </div>

                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>