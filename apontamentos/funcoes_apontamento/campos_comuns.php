<?php
// Campos comuns a vários apontamentos

function campo_data($id) {
    echo '<div class="form-campo">
        <label for="apt'. $id .'-data">Data</label>
        <input class="form-text only-num" type="date" name="apt'. $id .'data" id="apt'. $id .'-data" required>
    </div>';
}

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

function campo_quantidade($id) {
    echo '<div class="form-campo">
        <label for="apt'. $id .'-qtd">Quantidade</label>
        <input type="text" class="form-text" name="apt'. $id .'qtd" id="apt'. $id .'-qtd" placeholder="Insira a quantidade" required>
    </div>';
}

function campo_previsao_colheita($id) {
    echo '<div class="form-campo">
        <label for="apt'. $id .'-prev">Previsão de colheita (dias)</label>
        <input type="text" class="form-text only-num" name="apt'. $id .'prev" id="apt'. $id .'-prev" placeholder="Insira a previsão em dias" required>
    </div>';
}

function campo_obs($id) {
    echo '<div class="form-campo">
        <label for="apt'. $id .'-obs">Observações</label>
        <textarea class="form-text form-textarea" name="apt'. $id .'obs" id="apt'. $id .'-obs" placeholder="Insira aqui suas observações..."></textarea>
    </div>';
}
