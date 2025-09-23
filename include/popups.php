<!-- Overlay geral -->
<div id="popup-overlay" class="popup d-none">

    <!-- Confirmação de Cancelamento -->
    <div class="popup-box d-none" id="popup-cancel">
        <h2 class="popup-title">Deseja mesmo cancelar?</h2>
        <p class="popup-text">Todos os dados digitados serão perdidos e você terá que inseri-los novamente</p>

        <div class="popup-actions">
            <button class="popup-btn" type="button" id="form-cancel-no" onclick="closePopup()">Não</button>
            <button class="popup-btn" type="button" id="form-cancel-yes" onclick="location.reload()">Sim</button>
        </div>
    </div>

    <!-- Alerta de Sucesso -->
    <div class="popup-box success d-none" id="popup-success">
        <div class="popup-icon icon-check cor-branco"></div>
        <h2 class="popup-title">Dados atualizados com sucesso!</h2>

        <div class="popup-actions">
            <button class="popup-btn fundo-branco cor-verde" id="btn-ok" type="button" onclick="closePopup()">Ok</button>
        </div>
    </div>

    <!-- Alerta de Campos a preencher -->
    <div class="popup-box d-none" id="popup-failed">
        <h2 class="popup-title">Não foi possível salvar os dados</h2>
        <p class="popup-text">Verifique se todos os campos estão preenchidos e tente novamente.</p>

        <div class="popup-actions">
            <button class="popup-btn" type="button" onclick="closePopup()">Voltar</button>
        </div>
    </div>
    
    <!-- Alterar Propriedade -->
    <div class="popup-box v2 d-none" id="popup-prop">
        <h2 class="popup-title">Alterar Propriedade</h2>
        
        <div class="item-box prop-box v2">

            <?php
            // Insira aqui a função para pegar as propriedades do sistema
            $propriedades = [
                ['id' => '01', 'nome' => 'Propriedade 01'],
                ['id' => '02', 'nome' => 'Propriedade 01'],
                ['id' => '03', 'nome' => 'Propriedade 03']
            ];

            if(!empty($propriedades)){
                foreach($propriedades as $propriedade) {
                    echo '
                        <div class="item item-propriedade fundo-preto v3" id="prop-' . $propriedade['id'] . '">
                            <h4 class="item-title">' . $propriedade['nome'] . '</h4>
                            <div class="item-edit">
                                <button class="edit-btn" id="select-propriedade" type="button">
                                    Selecionar
                                </button>
                            </div>
                        </div>
                    ';
                }
            } else {
                echo '<div class="item-none">Nenhuma propriedade cadastrada.</div>';
            }

            ?>
        </div>

        <div class="popup-actions">
            <button class="popup-btn" type="button" onclick="closePopup()">Voltar</button>
        </div>
    </div>

</div>