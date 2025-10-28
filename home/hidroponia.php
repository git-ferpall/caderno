<?php
require_once __DIR__ . '/../configuracao/protect.php';

// ✅ Garante que o navegador vai renderizar HTML corretamente
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
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
        .item-bancada.bancada-selecionada {
        background-color: #4caf50 !important;
        color: white !important;
        border: 2px solid #2e7d32;
        transform: scale(1.02);
        transition: all 0.2s ease-in-out;
}
    </style>    
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

                // Aqui vai uma função pra pegar as estufas cadastradas, ou importar de um arquivo json
                require_once __DIR__ . '/../funcoes/carregar_hidroponia.php';
                $data = carregarHidroponia();

                $estufas = [];
                if ($data['ok'] && isset($data['estufas'])) {
                    $estufas = $data['estufas'];
                }



                if(!empty($estufas)){
                    foreach($estufas as $estufa) {
                        $area = ($estufa['area_m2'] == '') ? 'Não informado' : $estufa['area_m2'];
                        $obs = ($estufa['obs'] == '') ? 'Nenhuma observação' : $estufa['obs'];
                        $has_obs = ($estufa['obs'] == '') ? '' : 'v2';

                        echo '<div class="item item-propriedade item-estufa v2" id="estufa-' . $estufa['id'] . '">
                            <h4 class="item-title">' . $estufa['nome'] . '</h4>
                            <div class="item-edit">
                                <button class="edit-btn" id="edit-estufa-' . $estufa['id'] . '" type="button" onclick="selectEstufa(' . $estufa['id'] . ')">
                                    Selecionar
                                </button>
                            </div>
                        </div>

                        <div class="item-estufa-box d-none" id="estufa-' . $estufa['id'] . '-box">
                            <div class="item-estufa-header">
                                <div class="item-estufa-header-box">
                                    <div class="item-estufa-title">Área (m²)</div>
                                    <div class="item-estufa-text">' . $area . '</div>
                                </div>
                                <div class="item-estufa-header-box ' . $has_obs . '">
                                    <div class="item-estufa-title">Observações</div>
                                    <div class="item-estufa-text">' . $obs . '</div>
                                </div>
                            </div>
                            <div class="item-bancadas">
                                <h4 class="item-bancadas-title">Bancadas</h4>
                                <div class="item-bancadas-box">';
                        
                        if(!empty($estufa['bancadas']) && is_array($estufa['bancadas'])){
                            foreach ($estufa['bancadas'] as $bancada) {
                                $cultura = ($bancada['cultura'] == '') ? 'Não informado': $bancada['cultura'];
                                $bancada_obs = ($bancada['obs'] == '') ? 'Nenhuma observação' : $bancada['obs'];
                                $bancada_has_obs = ($bancada['obs'] == '') ? '' : 'v2';

                                $form_id = 'e-' . $estufa['id'] . '-b-' . $bancada['nome'];

                                echo '<button type="button" class="item-bancada" id="item-bancada-' . $bancada['nome'] . '-estufa-' . $estufa['id'] . '" onclick="selectBancada(\'' . strval($bancada['nome']) . '\', '. strval($estufa['id']) . ')">
                                    <div class="item-bancada-title">' . $bancada['nome'] . '</div>
                                </button>

                                <div class="item-bancada-content d-none" id="item-bancada-' . $bancada['nome'] . '-content-estufa-' . $estufa['id'] . '">
                                    <div class="item-bancada-header">

                                        <div class="item-bancada-header-box">
                                            <div class="item-bancada-header-title">Cultura/espécie</div>
                                            <div class="item-bancada-header-text">' . $cultura . '</div>
                                        </div>

                                        <div class="item-bancada-header-box ' . $bancada_has_obs . '">
                                            <div class="item-bancada-header-title">Observações</div>
                                            <div class="item-bancada-header-text">' . $bancada_obs . '</div>
                                        </div>

                                    </div>

                                    <div class="item-bancada-options">

                                        <button type="button" class="item-bancada-option bancada-defensivo v1" id="item-bancada-' . $bancada['nome'] . '-estufa-' . $estufa['id'] . '-defensivo">
                                            <div class="item-bancada-icon-box">
                                                <div class="item-bancada-icon icon-apt12"></div>
                                            </div>
                                            <div class="item-bancada-option-title">Aplicar Defensivo</div>
                                        </button>

                                        <form action="hidroponia.php" class="main-form form-defensivo d-none" id="add-<?php echo $form_id; ?>-defensivo">
                                            <div class="form-campo">
                                                <label for="def-' . $form_id . '-produto">Produto aplicado</label>
                                                <div class="form-box form-box-produto">
                                                    <select name="def-' . $form_id . '-produto" id="def-' . $form_id . '-produto" class="form-select form-text" required>
                                                        <option value="-">Selecione o produto aplicado</option>
                                                    </select>
                                                    <button class="add-btn add-produto" type="button">
                                                        <div class="btn-icon icon-plus cor-branco"></div>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="form-campo">
                                                <label class="item-label" for="def-' . $form_id . '-dose">Dose utilizada</label>
                                                <input type="text" class="form-text" name="def-' . $form_id . '-dose" id="def-' . $form_id . '-dose" placeholder="Dose utilizada">
                                            </div>

                                            <div class="form-campo">
                                                <label class="item-label" for="def-' . $form_id . '-motivo">Motivo</label>
                                                <div class="form-radio-box" id="def-' . $form_id . '-motivo">
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="def-' . $form_id . '-motivo" value="1" checked/>
                                                        Prevenção
                                                    </label>
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="def-' . $form_id . '-motivo" value="2" />
                                                        Controle
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-campo">
                                                <label for="def-' . $form_id . '-obs">Observações</label>
                                                <textarea class="form-text form-textarea" name="def-' . $form_id . '-obs" id="def-' . $form_id . '-obs" placeholder="Insira aqui suas observações..."></textarea>
                                            </div>

                                            <div class="form-submit">
                                                <button class="main-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-def-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                                    <span class="main-btn-text">Cancelar</span>
                                                </button>
                                                <button class="main-btn fundo-verde form-save" id="form-save-def-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-check cor-verde"></div> -->
                                                    <span class="main-btn-text">Salvar</span>
                                                </button>
                                            </div>
                                        </form>

                                        <button type="button" class="item-bancada-option bancada-fertilizante v2" id="item-bancada-' . $bancada['nome'] . '-estufa-' . $estufa['id'] . '-fertilizante">
                                            <div class="item-bancada-icon-box">
                                                <div class="item-bancada-icon icon-apt10"></div>
                                            </div>
                                            <div class="item-bancada-option-title">Aplicar Fertilizante</div>
                                        </button>

                                        <form action="hidroponia.php" class="main-form form-fertilizante d-none" id="add-' . $form_id . '-fertilizante">

                                            <div class="form-campo">
                                                <label for="fert-' . $form_id . '-produto">Produto aplicado</label>
                                                <div class="form-box form-box-produto">
                                                    <select name="fert-' . $form_id . '-produto" id="fert-' . $form_id . '-produto" class="form-select form-text" required>
                                                        <option value="-">Selecione o produto aplicado</option>
                                                    </select>
            
                                                </div>
                                                <!-- Campo extra que só aparece se selecionar "Outro" -->
                                                <input 
                                                    type="text" 
                                                    id="fert-' . $form_id . '-produto-outro" 
                                                    name="fert-' . $form_id . '-produto-outro"
                                                    class="form-text fertilizante-outro"
                                                    placeholder="Digite o nome do fertilizante"
                                                    style="display:none; margin-top:8px;"
                                                >
                                            </div>


                                            <div class="form-campo">
                                                <label class="item-label" for="fert-' . $form_id . '-dose">Dose utilizada</label>
                                                <input type="text" class="form-text" name="fert-' . $form_id . '-dose" id="fert-' . $form_id . '-dose" placeholder="Dose utilizada">
                                            </div>

                                            <div class="form-campo">
                                                <label class="item-label" for="fert-' . $form_id . '-tipo">Tipo de aplicação</label>
                                                <div class="form-radio-box" id="fert-' . $form_id . '-tipo">
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="fert-' . $form_id . '-tipo" value="1" checked/>
                                                        Foliar
                                                    </label>
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="fert-' . $form_id . '-tipo" value="2" />
                                                        Solução
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-campo">
                                                <label for="fert-' . $form_id . '-obs">Observações</label>
                                                <textarea class="form-text form-textarea" name="fert-' . $form_id . '-obs" id="fert-' . $form_id . '-obs" placeholder="Insira aqui suas observações..."></textarea>
                                            </div>

                                            <div class="form-submit">
                                                <button class="main-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-fert-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                                    <span class="main-btn-text">Cancelar</span>
                                                </button>
                                                <button class="main-btn fundo-verde form-save" id="form-save-fert-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-check cor-verde"></div> -->
                                                    <span class="main-btn-text">Salvar</span>
                                                </button>
                                            </div>

                                        </form>

                                        <button type="button" class="item-bancada-option bancada-colheita v3" id="item-bancada-' . $bancada['nome'] . '-estufa-' . $estufa['id'] . '-colheita">
                                            <div class="item-bancada-icon-box">
                                                <div class="item-bancada-icon icon-apt3"></div>
                                            </div>
                                            <div class="item-bancada-option-title">Colheita</div>
                                        </button>

                                        <form action="hidroponia.php" class="main-form form-colheita d-none" id="add-' . $form_id . '-colheita">

                                            <div class="form-campo">
                                                <label class="item-label" for="col-' . $form_id . '-qtd">Quantidade colhida</label>
                                                <input type="text" class="form-text onlynum" name="col-' . $form_id . '-qtd" id="col-' . $form_id . '-qtd" placeholder="Quantidade colhida">
                                            </div>

                                            <div class="form-campo">
                                                <label class="item-label" for="col-' . $form_id . '-dest">Destino</label>
                                                <div class="form-radio-box" id="col-' . $form_id . '-dest">
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="col-' . $form_id . '-dest" value="1" checked/>
                                                        Comercialização
                                                    </label>
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="col-' . $form_id . '-dest" value="2" />
                                                        Consumo
                                                    </label>
                                                    <label class="form-radio v2">
                                                        <input type="radio" name="col-' . $form_id . '-dest" value="3"/>
                                                        Descarte
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-campo">
                                                <label for="col-' . $form_id . '-obs">Observações</label>
                                                <textarea class="form-text form-textarea" name="col-' . $form_id . '-obs" id="col-' . $form_id . '-obs" placeholder="Insira aqui suas observações..."></textarea>
                                            </div>

                                            <div class="form-submit">
                                                <button class="main-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-col-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-x cor-cinza-b"></div> -->
                                                    <span class="main-btn-text">Cancelar</span>
                                                </button>
                                                <button class="main-btn fundo-verde form-save" id="form-save-col-' . $form_id . '" type="button">
                                                    <!-- <div class="btn-icon icon-check cor-verde"></div> -->
                                                    <span class="main-btn-text">Salvar</span>
                                                </button>
                                            </div>

                                        </form>

                                        <button type="button" class="item-bancada-option bancada-historico v4" id="item-bancada-' . $bancada['nome'] . '-estufa-' . $estufa['id'] . '-historico">
                                            <div class="item-bancada-icon-box">
                                                <div class="item-bancada-icon icon-apt17"></div>
                                            </div>
                                            <div class="item-bancada-option-title">Histórico</div>
                                        </button>

                                        <div class="main-form form-historico d-none" id="' . $form_id . '-historico">
                                            <div class="historico-none">Nenhum registro encontrado.</div>
                                        </div>

                                    </div>

                                    <div class="item-bancada-edit">
                                        <button class="item-btn item-bancada-btn fundo-cinza-b cor-preto" id="bancada-' . $bancada['nome'] . '-cancel" type="button" onclick="voltarEstufa('. strval($estufa['id']) . ')">
                                            <span class="main-btn-text">Voltar</span>
                                        </button>
                                        <!--
                                        <button class="item-btn item-bancada-btn" id="bancada-' . $bancada['nome'] . '-remove" type="button">
                                            <span class="main-btn-text">Remover Bancada</span>
                                        </button>
                                        -->
                                    </div>

                                </div>
                                ';
                            }
                        } else {
                            echo '<div class="item-none">Nenhuma bancada cadastrada.</div>';
                        }
                                
                        echo '</div>
                            <form action="hidroponia.php" class="main-form form-bancada" id="add-bancada-estufa-<?php echo $estufa['id']; ?>">
                                <div class="item-add">
                                    <button class="main-btn btn-alter btn-alter-item fundo-verde" 
                                            id="bancada-add-estufa-<?php echo $estufa['id']; ?>" 
                                            type="button">
                                        <div class="btn-icon icon-plus cor-verde"></div>
                                        <span class="main-btn-text">Nova Bancada</span>
                                    </button>
                                </div>

                                <div class="item-add-box" id="item-add-bancada-estufa-<?php echo $estufa['id']; ?>">
                                    <div class="item-add-box-p">

                                        <div class="form-campo">
                                            <label class="item-label" for="b-nome-estufa-<?php echo $estufa['id']; ?>">Número/Nome da Bancada</label>
                                            <input type="text" 
                                                class="form-text" 
                                                name="b-nome" 
                                                id="b-nome-estufa-<?php echo $estufa['id']; ?>" 
                                                placeholder="Ex: 01, 02..." 
                                                required>
                                        </div>

                                        <div class="form-campo">
                                            <label class="item-label" for="b-produto-estufa-<?php echo $estufa['id']; ?>">Cultura/Produto</label>
                                            <div class="form-box form-box-produto">
                                                <select name="b-produto" 
                                                        id="b-produto-estufa-<?php echo $estufa['id']; ?>" 
                                                        class="form-select form-text produto-select" 
                                                        required>
                                                    <option value="">Selecione o produto</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-campo">
                                            <label for="b-obs-estufa-<?php echo $estufa['id']; ?>">Observações</label>
                                            <textarea class="form-text form-textarea" 
                                                    name="b-obs" 
                                                    id="b-obs-estufa-<?php echo $estufa['id']; ?>" 
                                                    placeholder="Insira aqui suas observações..."></textarea>
                                        </div>

                                        <div class="form-submit">
                                            <button class="item-btn fundo-cinza-b cor-preto form-cancel" 
                                                    id="form-cancel-bancada-estufa-<?php echo $estufa['id']; ?>" 
                                                    type="button">
                                                <span class="main-btn-text">Cancelar</span>
                                            </button>
                                            <button class="item-btn fundo-verde form-save" 
                                                    id="form-save-bancada-estufa-<?php echo $estufa['id']; ?>" 
                                                    type="button">
                                                <span class="main-btn-text">Salvar</span>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>';
                    }
                } else {
                    echo '<div class="item-none">Nenhuma estufa cadastrada.</div>';
                }
                
                ?>
                    
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
    <script src="../js/hidroponia.js"></script>
    <script src="../js/hidroponia_fertilizante.js"></script>
    
    <?php include '../include/footer.php' ?>
</body>
</html>