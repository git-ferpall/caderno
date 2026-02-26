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
                
            </div>

            <div class="sistema-main container">
                <div class="apt-box">
                    
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

                        // mapa de ID → nome do arquivo
                        $mapaArquivos = [
                            1  => 'Plantio.php',
                            2  => 'Transplantio.php',
                            3  => 'Colheita.php',
                            4  => 'Climatico.php',
                            5  => 'Fertilizante.php',
                            6  => 'Herbicida.php',
                            7  => 'Fungicida.php',
                            8  => 'Inseticida.php',
                            9  => 'AdubacaoCalcario.php',
                            10 => 'AdubacaoOrganica.php',
                            11 => 'Irrigacao.php',
                            12 => 'ControleAgua.php',
                            13 => 'MoscaFrutas.php',
                            14 => 'PragasDoencas.php',
                            15 => 'ManejoIntegrado.php',
                            16 => 'Erradicacao.php',
                            17 => 'RevisaoMaquinas.php',
                            18 => 'ColetaAnalise.php',
                            19 => 'VisitaTecnica.php',
                            20 => 'Personalizado.php'
                        ];

                        // botões
                        foreach ($apts as $apt) {
                            $id = $apt['id'];
                            $nome = $apt['nome'];
                            $arquivo = $mapaArquivos[$id] ?? null;

                            if ($arquivo) {
                                echo '<a href="../home/' . $arquivo . '" class="apt-button fundo-apt' . $id . '">
                                    <div class="apt-icon-box">
                                        <div class="apt-icon icon-apt' . $id . ' cor-apt' . $id . '"></div>
                                    </div>
                                    <h5 class="apt-title">' . htmlspecialchars($nome) . '</h5>
                                </a>';
                            }
                        #echo '<button class="apt-button fundo-apt' . $id . '" type="button" id="apt' . $id . '" onclick="novoApontamento(' . $aptJson . ')">
                        #    <div class="apt-icon-box">
                        #        <div class="apt-icon icon-apt' . $id . ' cor-apt' . $id . '"></div>
                        #    </div>
                        #    <h5 class="apt-title">' . htmlspecialchars($nome) . '</h5>
                       # </button>'; 
                    }

                    // Adiciona os formulários de adição de cada apontamento
                    foreach ($apts as $apt) {
                        $id = $apt['id'];

                        echo '<div class="apt-add d-none" id="add-apt'. $id .'"> <form action="apontamento.php" class="main-form" id="apt'. $id .'-form">';
                        campo_data($id);

                        if ($id == 1) {
                        // Apontamento 1 - Plantio
                            campo_area_cultivada($id); 
                            campo_produto_cultivado($id);
                            campo_quantidade($id);
                            campo_previsao_colheita($id);
                            campo_obs($id);

                        } else if ($id == 2) {
                        // Apontamento 2 - Transplantio
                            campo_produto_cultivado($id);
                            campo_area_origem($id);
                            campo_area_destino($id);
                            campo_quantidade($id);
                            campo_obs($id);                            

                        } else if ($id == 3) {
                        // Apontamento 3 - Colheita
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_producao($id);
                            campo_n_romaneio($id);
                            campo_obs($id);
                            
                        } else if ($id == 4) {
                        // Apontamento 4 - Registros Climáticos
                            campo_precipitacao($id);
                            campo_umidade($id);
                            campo_temp_min($id);
                            campo_temp_max($id);
                            campo_tec_responsavel($id);
                            campo_obs($id);
                            
                        } else if ($id == 5) {
                        // Apontamento 5 - Fertilizante
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_tipo_fertilizante($id);
                            campo_produto_utilizado($id);
                            campo_forma_aplicacao($id);
                            campo_quantidade_kg($id);
                            campo_n_referencia_amostra($id);
                            campo_obs($id);
                            
                        } else if ($id == 6) {
                        // Apontamento 6 - Herbicida
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_maquina_utilizada($id);
                            campo_produto_utilizado($id);
                            campo_dose($id);
                            campo_obs($id);
                            
                        } else if ($id == 7) {
                        // Apontamento 7 - Fungicida
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_maquina_utilizada($id);
                            campo_produto_utilizado($id);
                            campo_dose($id);
                            campo_obs($id);
                            
                        } else if ($id == 8) {
                        // Apontamento 8 - Inseticida
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_maquina_utilizada($id);
                            campo_produto_utilizado($id);
                            campo_dose($id);
                            campo_obs($id);
                            
                        } else if ($id == 9) {
                        // Apontamento 9 - Adubação (calcário / gesso)
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_produto_utilizado($id);
                            campo_tipo($id);
                            campo_quantidade_t($id);
                            campo_n_referencia_amostra($id);
                            campo_prnt($id);
                            campo_forma_aplicacao($id);
                            campo_obs($id);
                            
                        } else if ($id == 10) {
                        // Apontamento 10 - Adubação Orgânica
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_produto_utilizado($id);
                            campo_tipo($id);
                            campo_quantidade_kgp($id);
                            campo_n_referencia_amostra($id);
                            campo_forma_aplicacao($id);
                            campo_obs($id);
                            
                        } else if ($id == 11) {
                        // Apontamento 11 - Irrigação
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_tempo_irrigacao($id);
                            campo_vol_aplicado($id);
                            campo_obs($id);
                            
                        } else if ($id == 12) {
                        // Apontamento 12 - Controle da Água
                            campo_entrada($id);
                            campo_saida($id);
                            campo_ph($id);
                            campo_obs($id);
                            
                        } else if ($id == 13) {
                        // Apontamento 13 - Mosca das frutas
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_especie_mosca($id);
                            campo_tipo_armadilha($id);
                            campo_n_armadilhas($id);
                            campo_n_moscas($id);
                            campo_moscas_armadilha($id);
                            campo_obs($id);
                            
                        } else if ($id == 14) {
                        // Apontamento 14 - Pragas e doenças
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_nome_praga($id);
                            campo_aplicacao_defensivos($id);
                            campo_maquina_utilizada($id);
                            campo_produto_utilizado($id);
                            campo_dosagem($id);
                            campo_calda($id);
                            campo_tec_responsavel($id);
                            campo_obs($id);
                            
                        } else if ($id == 15) {
                        // Apontamento 15 - Manejo Integrado
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_nome_praga($id);
                            campo_tipo_praga($id);
                            campo_pontos($id);
                            campo_obs($id);
                            echo '<div class="form-box">';
                            campo_tec_responsavel($id);
                            campo_crea($id);
                            echo '</div>';
                            campo_img($id);
                            campo_assinaturas($id);
                            
                        } else if ($id == 16) {
                        // Apontamento 16 - Erradicação
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_nome_praga($id);
                            campo_quantidade_p($id);
                            campo_obs($id);
                            
                        } else if ($id == 17) {
                        // Apontamento 17 - Revisão de Máquinas
                            campo_maquina($id);
                            campo_horimetro($id);
                            campo_reposicoes($id);
                            campo_obs($id);
                            
                        } else if ($id == 18) {
                        // Apontamento 18 - Coleta e Análise
                            campo_area_cultivada($id);
                            campo_produto_cultivado($id);
                            campo_tipo_analise($id);
                            campo_parte($id);
                            campo_tec_responsavel($id);
                            campo_obs($id);
                            
                        } else if ($id == 19) {
                        // Apontamento 19 - Visita Técnica
                            campos_visita($id);
                            echo '<div class="form-box">';
                            campo_tec_responsavel($id);
                            campo_crea($id);
                            echo '</div>';
                            campo_img($id);
                            campo_assinaturas($id);
                            
                        } else {
                        // Apontamento 20 - Personalizado
                            campo_obs($id);
                            campo_img($id);
                        }

                        echo '<div class="form-submit">
                            <button class="main-btn form-cancel fundo-vermelho" id="form-cancel-apt' . $id . '" type="button">
                                <span class="main-btn-text">Cancelar</span>
                            </button>
                            <button class="main-btn form-save fundo-verde" id="form-save-apt' . $id . '" type="button">
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
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-data">Data</label>
                            <input class="form-text only-num" type="date" name="apt'. $id .'data" id="apt'. $id .'-data"  required>
                        </div>';
                    }

                    // Adiciona o campo "Área cultivada"
                    function campo_area_cultivada($id) {
                        echo '<div class="form-campo">
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
                        echo '<div class="form-campo">
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
                        echo '<div class="form-campo">
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
                        echo '<div class="form-campo">
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

                    // Adiciona o campo "Máquina utilizada"
                    function campo_maquina_utilizada($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-maquina-utilizada">Máquina utilizada</label>
                            <div class="form-box form-box-maquina">
                                <select name="apt'. $id .'maquina-utilizada" id="apt'. $id .'-maquina-utilizada" class="form-select form-text" required>
                                    <option value="-">Selecione a máquina utilizada</option>
                                </select>
                                <button class="add-btn add-maquina" type="button">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Máquina"
                    function campo_maquina($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-maquina">Máquina</label>
                            <div class="form-box form-box-maquina">
                                <select name="apt'. $id .'maquina" id="apt'. $id .'-maquina" class="form-select form-text" required>
                                    <option value="-">Selecione a máquina</option>
                                </select>
                                <button class="add-btn add-maquina" type="button">
                                    <div class="btn-icon icon-plus cor-branco"></div>
                                </button>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Produto utilizado (nome comercial)"
                    function campo_produto_utilizado($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-produto-utilizado">Produto utilizado (nome comercial)</label>
                            <input type="text" class="form-text" name="apt'. $id .'produto-utilizado" id="apt'. $id .'-produto-utilizado" placeholder="Insira o nome do produto utilizado" required>
                        </div>';
                    }

                    // Adiciona o campo "Nome da praga ou doença"
                    function campo_nome_praga($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-nome-praga">Nome da praga ou doença</label>
                            <input type="text" class="form-text" name="apt'. $id .'nome-praga" id="apt'. $id .'-nome-praga" placeholder="Insira o nome da praga ou doença" required>
                        </div>';
                    }

                    // Adiciona o campo "Calda (litros/ha)"
                    function campo_calda($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-calda">Calda (litros/ha)</label>
                            <input type="text" class="form-text" name="apt'. $id .'calda" id="apt'. $id .'-calda" placeholder="Insira o valor da calda em litros/ha" required>
                        </div>';
                    }

                    // Adiciona o campo "Dosagem (g ou ml para 100 litros)"
                    function campo_dosagem($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-dosagem">Dosagem (g ou ml para 100 litros)</label>
                            <input type="text" class="form-text" name="apt'. $id .'dosagem" id="apt'. $id .'-dosagem" placeholder="Insira o valor da dosagem em g ou ml para 100 litros" required>
                        </div>';
                    }

                    // Adiciona o campo "Dose (l/ha)"
                    function campo_dose($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-dose">Dose (l/ha)</label>
                            <input type="text" class="form-text" name="apt'. $id .'dose" id="apt'. $id .'-dose" placeholder="Insira a dose" required>
                        </div>';
                    }

                    // Adiciona o campo "PRNT"
                    function campo_prnt($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-prnt">PRNT</label>
                            <input type="text" class="form-text" name="apt'. $id .'prnt" id="apt'. $id .'-prnt" placeholder="Insira o PRNT" required>
                        </div>';
                    }

                    // Adiciona o campo "Horímetro"
                    function campo_horimetro($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-horimetro">Horímetro</label>
                            <input type="text" class="form-text" name="apt'. $id .'horimetro" id="apt'. $id .'-horimetro" placeholder="Insira o horímetro" required>
                        </div>';
                    }

                    // Adiciona o campo "Tipo"
                    function campo_tipo($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-tipo">Tipo</label>
                            <input type="text" class="form-text" name="apt'. $id .'tipo" id="apt'. $id .'-tipo" placeholder="Insira o tipo" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade"
                    function campo_quantidade($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-qtd">Quantidade</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd" id="apt'. $id .'-qtd" placeholder="Insira a quantidade" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade (Kg/ha/mês)"
                    function campo_quantidade_kg($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-qtd-kg">Quantidade (Kg/ha/mês)</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd-kg" id="apt'. $id .'-qtd-kg" placeholder="Insira a quantidade" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade (kg/planta)"
                    function campo_quantidade_kgp($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-qtd-kgp">Quantidade (kg/planta)</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd-kgp" id="apt'. $id .'-qtd-kgp" placeholder="Insira a quantidade" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade total de plantas"
                    function campo_quantidade_p($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-qtd-p">Quantidade total de plantas</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd-p" id="apt'. $id .'-qtd-p" placeholder="Insira a quantidade total de plantas" required>
                        </div>';
                    }

                    // Adiciona o campo "Quantidade (t/ha)"
                    function campo_quantidade_t($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-qtd-t">Quantidade (t/ha)</label>
                            <input type="text" class="form-text" name="apt'. $id .'qtd-t" id="apt'. $id .'-qtd-t" placeholder="Insira a quantidade" required>
                        </div>';
                    }

                    // Adiciona o campo "N° de referência da amostra"
                    function campo_n_referencia_amostra($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-n-ref-am">N° de referência da amostra</label>
                            <input type="text" class="form-text" name="apt'. $id .'n-ref-am" id="apt'. $id .'-n-ref-am" placeholder="Insira a quantidade em número" required>
                        </div>';
                    }

                    // Adiciona o campo "Previsão de colheita (dias)"
                    function campo_previsao_colheita($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-prev">Previsão de colheita (dias)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'prev" id="apt'. $id .'-prev" placeholder="Insira a previsão em dias" required>
                        </div>';
                    }

                    // Adiciona o campo "Observações"
                    function campo_obs($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-obs">Observações</label>
                            <textarea class="form-text form-textarea" name="apt'. $id .'obs" id="apt'. $id .'-obs" placeholder="Insira aqui suas observações..."></textarea>
                        </div>';
                    }

                    // Adiciona o campo "Reposições / Manutenções"
                    function campo_reposicoes($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-rep">Reposições / Manutenções</label>
                            <textarea class="form-text form-textarea" name="apt'. $id .'rep" id="apt'. $id .'-rep" placeholder="Insira aqui as reposições ou manutenções realizadas..."></textarea>
                        </div>';
                    }

                    // Adiciona o campo "Produção (kg ou un / talhão)"
                    function campo_producao($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-producao">Produção (kg ou un / talhão)</label>
                            <input type="text" class="form-text" name="apt'. $id .'producao" id="apt'. $id .'-producao" placeholder="Insira a produção" required>
                        </div>';
                    }

                    // Adiciona o campo "N° do romaneio"
                    function campo_n_romaneio($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-n-romaneio">N° do romaneio</label>
                            <input type="text" class="form-text" name="apt'. $id .'n-romaneio" id="apt'. $id .'-n-romaneio" placeholder="Insira o número do romaneio" required>
                        </div>';
                    }

                    // Adiciona o campo "N° de armadilhas"
                    function campo_n_armadilhas($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-n-arm">N° de armadilhas</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'n-arm" id="apt'. $id .'-n-arm" placeholder="Insira o número de armadilhas" required>
                        </div>';
                    }

                    // Adiciona o campo "N° de moscas"
                    function campo_n_moscas($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-n-moscas">N° de moscas</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'n-moscas" id="apt'. $id .'-n-moscas" placeholder="Insira o número de moscas" required>
                        </div>';
                    }

                    // Adiciona o campo "Moscas por armadilha"
                    function campo_moscas_armadilha($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-moscas-arm">Moscas por armadilha</label>
                            <input type="text" class="form-text  only-num" name="apt'. $id .'moscas-arm" id="apt'. $id .'-moscas-arm" placeholder="Insira a quantidade de moscas por armadilha" required>
                        </div>';
                    }

                    // Adiciona o campo "Tipo de fertilizante"
                    function campo_tipo_fertilizante($id) {
                        echo '<div class="form-campo">
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

                    // Adiciona o campo "Tipo de armadilha"
                    function campo_tipo_armadilha($id) {
                        echo '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-tipo-arm">Tipo de armadilha</label>
                            <div class="form-radio-box" id="apt'. $id .'-tipo-arm">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-arm" value="1" checked/>
                                    McPhail
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-arm" value="2" />
                                    Jackson
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-arm" value="3" />
                                    Outra
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Tipo de praga"
                    function campo_tipo_praga($id) {
                        echo '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-tipo-praga">Tipo de praga</label>
                            <div class="form-radio-box" id="apt'. $id .'-tipo-praga">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-praga" value="1" checked/>
                                    Doença
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-praga" value="2" />
                                    Predador
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-praga" value="3" />
                                    Outra
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Tipo de análise"
                    function campo_tipo_analise($id) {
                        echo '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-tipo-analise">Tipo de análise</label>
                            <div class="form-radio-box" id="apt'. $id .'-tipo-analise">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-analise" value="1" checked/>
                                    Química
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'tipo-analise" value="2" />
                                    Microbiológica
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Parte coletada"
                    function campo_parte($id) {
                        echo '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-parte">Parte Coletada</label>
                            <div class="form-radio-box" id="apt'. $id .'-parte">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'parte" value="1" checked/>
                                    Solo
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'parte" value="2" />
                                    Limbo
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'parte" value="3" />
                                    Planta
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Aplicação de defensivos"
                    function campo_aplicacao_defensivos($id) {
                        echo '<div class="form-campo">
                            <label class="item-label" for="apt'. $id .'-ap-def">Aplicação de defensivos</label>
                            <div class="form-radio-box" id="apt'. $id .'-ap-def">
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'ap-def" value="1" checked/>
                                    Sim
                                </label>
                                <label class="form-radio v2">
                                    <input type="radio" name="apt'. $id .'ap-def" value="2" />
                                    Não
                                </label>
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Forma de aplicação"
                    function campo_forma_aplicacao($id) {
                        if($id == 9) $v3 = '';
                        else $v3 = '<label class="form-radio v2">
                            <input type="radio" name="apt'. $id .'forma-apl" value="3" />
                            Pulverização
                        </label>';
                        echo '<div class="form-campo">
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
                                ' . $v3 . '
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Precipitação (mm)"
                    function campo_precipitacao($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-precipitacao">Precipitação (mm)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'precipitacao" id="apt'. $id .'-precipitacao" placeholder="Insira a precipitação em mm" required>
                        </div>';
                    }

                    // Adiciona o campo "Umidade (%)"
                    function campo_umidade($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-umidade">Umidade (%)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'umidade" id="apt'. $id .'-umidade" placeholder="Insira a úmidade em %" required>
                        </div>';
                    }

                    // Adiciona o campo "Temperatura Mínima (°C)"
                    function campo_temp_min($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-temp-min">Temperatura Mínima (°C)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'temp-min" id="apt'. $id .'-temp-min" placeholder="Insira a temperatura mínima em °C" required>
                        </div>';
                    }

                    // Adiciona o campo "Temperatura Máxima (°C)"
                    function campo_temp_max($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-temp-max">Temperatura Máxima (°C)</label>
                            <input type="text" class="form-text only-num" name="apt'. $id .'temp-max" id="apt'. $id .'-temp-max" placeholder="Insira a temperatura máxima em °C" required>
                        </div>';
                    }

                    // Adiciona o campo "Técnico Responsável"
                    function campo_tec_responsavel($id) {
                        echo '<div class="form-campo f5">
                            <label for="apt'. $id .'-tec-responsavel">Técnico Responsável</label>
                            <input type="text" class="form-text" name="apt'. $id .'tec-responsavel" id="apt'. $id .'-tec-responsavel" placeholder="Nome do técnico responsável" required>
                        </div>';
                    }

                    // Adiciona o campo "CREA N°"
                    function campo_crea($id) {
                        echo '<div class="form-campo f2">
                            <label for="apt'. $id .'-crea">CREA N°</label>
                            <input type="text" class="form-text form-num only-num" name="apt'. $id .'crea" id="apt'. $id .'-crea" placeholder="CREA" required>
                        </div>';
                    }

                    // Adiciona o campo "Tempo de irrigação (horas)"
                    function campo_tempo_irrigacao($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-tempo-irrigacao">Tempo de irrigação (horas)</label>
                            <input type="text" class="form-text" name="apt'. $id .'tempo-irrigacao" id="apt'. $id .'-tempo-irrigacao" placeholder="Insira o tempo de irrigação em horas" required>
                        </div>';
                    }

                    // Adiciona o campo "Volume aplicado"
                    function campo_vol_aplicado($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-vol-aplicado">Volume aplicado</label>
                            <input type="text" class="form-text" name="apt'. $id .'vol-aplicado" id="apt'. $id .'-vol-aplicado" placeholder="Insira o volume aplicado" required>
                        </div>';
                    }

                    // Adiciona o campo "Entrada (EC)"
                    function campo_entrada($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-entrada">Entrada (EC)</label>
                            <input type="text" class="form-text" name="apt'. $id .'entrada" id="apt'. $id .'-entrada" placeholder="Insira o valor de entrada" required>
                        </div>';
                    }

                    // Adiciona o campo "Saída (EC)"
                    function campo_saida($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-saida">Saída (EC)</label>
                            <input type="text" class="form-text" name="apt'. $id .'saida" id="apt'. $id .'-saida" placeholder="Insira o valor de saída" required>
                        </div>';
                    }

                    // Adiciona o campo "PH da água"
                    function campo_ph($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-ph">PH da água</label>
                            <input type="text" class="form-text" name="apt'. $id .'ph" id="apt'. $id .'-ph" placeholder="Insira o valor do PH da água" required>
                        </div>';
                    }

                    // Adiciona o campo "Espécie da mosca"
                    function campo_especie_mosca($id) {
                        echo '<div class="form-campo">
                            <label for="apt'. $id .'-especie">Espécie da mosca</label>
                            <input type="text" class="form-text" name="apt'. $id .'ph" id="apt'. $id .'-ph" placeholder="Insira a espécie da mosca" required>
                        </div>';
                    }

                    // Adiciona o campo "Pontos de amostragem"
                    function campo_pontos($id) {
                        echo '<div class="form-box">
                            <div class="form-campo f6">
                                <label for="apt'. $id .'-pontos" class="form-label">Pontos de amostragem</label>
                                <div class="form-range-box range-wrapper">
                                    <input type="range" min="0" max="10" value="5" class="form-range" name="apt'. $id .'pontos" id="apt'. $id .'-pontos">
                                    <div class="form-range-value" id="apt'. $id .'-pontos-valor">5</div>
                                </div>
                            </div>
                            <div class="form-campo f2">
                                <label for="apt'. $id .'-pontos-total" class="form-label">Total</label>
                                <input type="text" class="form-text form-num form-bold only-num" name="apt'. $id .'pontos-total" id="apt'. $id .'-pontos-total" placeholder="0" value="0">
                            </div>
                            <div class="form-campo f2">
                                <label for="apt'. $id .'-pontos-media" class="form-label">Média</label>
                                <input type="text" class="form-text form-num form-bold only-num" name="apt'. $id .'pontos-media" id="apt'. $id .'-pontos-media" placeholder="0" value="0">
                            </div>
                        </div>';
                    }

                    // Adiciona o campo "Imagens (galeria ou câmera)"
                    function campo_img($id) {
                        echo '<div class="form-campo">
                            <label class="form-label">Imagens (galeria ou câmera)</label>

                            <div class="form-upload-box">
                                <label for="apt'. $id .'-img" class="form-upload" id="form-upload-btn">
                                    <div class="btn-icon icon-plus"></div>
                                </label>
                                <div class="form-upload-preview d-none">
                                    <img class="form-upload-preview-img" src="" alt="Imagem selecionada" />
                                    <div class="form-upload-info">
                                        <span class="form-upload-img-name"></span>
                                        <button class="form-upload-edit" type="button">
                                            <div class="edit-icon icon-pen"></div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <input type="file" class="d-none apt-input" id="apt'. $id .'-img" name="apt'. $id .'img" accept="image/*" />
                        </div>';
                    }

                    // Adiciona os campos "Assinatura do Técnico" e "Assinatura do Produtor"
                    function campo_assinaturas($id) {
                        echo '<div class="form-box">
                            <div class="form-campo f1">
                                <label class="form-label">Assinatura do Técnico</label>

                                <div class="form-upload-box">
                                    <label for="apt'. $id .'-ass-tec" class="form-upload" id="form-upload-btn">
                                        <div class="btn-icon icon-plus"></div>
                                    </label>
                                    <div class="form-upload-preview d-none">
                                        <img class="form-upload-preview-img" src="" alt="Imagem selecionada" />
                                        <div class="form-upload-info">
                                            <span class="form-upload-img-name"></span>
                                            <button class="form-upload-edit" type="button">
                                                <div class="edit-icon icon-pen"></div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <input type="file" class="d-none apt-input" id="apt'. $id .'-ass-tec" name="apt'. $id .'ass-tec" accept="image/*" />
                            </div>

                            <div class="form-campo f1">
                                <label class="form-label">Assinatura do Produtor</label>

                                <div class="form-upload-box">
                                    <label for="apt'. $id .'-ass-prod" class="form-upload" id="form-upload-btn">
                                        <div class="btn-icon icon-plus"></div>
                                    </label>
                                    <div class="form-upload-preview d-none">
                                        <img class="form-upload-preview-img" src="" alt="Imagem selecionada" />
                                        <div class="form-upload-info">
                                            <span class="form-upload-img-name"></span>
                                            <button class="form-upload-edit" type="button">
                                                <div class="edit-icon icon-pen"></div>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <input type="file" class="d-none apt-input" id="apt'. $id .'-ass-prod" name="apt'. $id .'ass-prod" accept="image/*" />
                            </div>
                        </div>';
                    }

                    function campos_visita($id) {
                        $itens = [
                            ['id' => 'tratamentos-fit', 'title' => 'Tratamentos fitossanitários'],
                            ['id' => 'adubacao-min', 'title' => 'Adubação mineral e orgânica'], 
                            ['id' => 'manejo-cob', 'title' => 'Manejo da cobertura verde'], 
                            ['id' => 'colheita', 'title' => 'Colheita'], 
                            ['id' => 'revisao-maq', 'title' => 'Revisão de maquinário'], 
                            ['id' => 'analise-solo', 'title' => 'Análise de solo'], 
                            ['id' => 'analise-fol', 'title' => 'Análise foliar'], 
                            ['id' => 'analise-res', 'title' => 'Análise de resíduos e agrotóxicos']
                        ];
                        foreach($itens as $item) {
                            $item_id = $item['id'];
                            $nome = $item['title'];
                            echo '<div class="form-campo">
                                <label class="item-label" for="apt'. $id .'-'. $item_id .'">'. $nome .'</label>
                                <div class="form-radio-box" id="apt'. $id .'-'. $item_id .'">
                                    <label class="form-radio v2 full">
                                        <input type="radio" name="apt'. $id . $item_id .'" value="1" checked/>
                                        Sim
                                    </label>
                                    <label class="form-radio v2 full">
                                        <input type="radio" name="apt'. $id . $item_id .'" value="2" />
                                        Não
                                    </label>
                                </div>
                                <textarea class="form-text form-textarea v2" name="apt'. $id . $item_id .'" id="apt'. $id .'-'. $item_id .'" placeholder="Observações..."></textarea>
                            </div>';
                        }
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