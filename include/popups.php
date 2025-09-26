
<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Pega user_id via sessão ou JWT
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$propriedades = [];
if ($user_id) {
    $stmt = $mysqli->prepare("SELECT * FROM propriedades WHERE user_id = ? ORDER BY ativo DESC, created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $propriedades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!-- Overlay geral -->
 <style>
    .badge-ativa {
    display: inline-block;
    margin-left: 10px;
    padding: 2px 6px;
    background: #4caf50;
    color: #fff;
    border-radius: 6px;
    font-size: 12px;
        font-weight: bold;
    }
    .item-propriedade.selecionada {
        border: 2px solid #2196f3;
        background: #1e1e1e;
    }
    .select-propriedade.selecionada {
    background-color: #4caf50; /* verde */
    color: #fff;
    cursor: default;
    }
 </style>   
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

    <!-- Alerta de Sucesso Propriedade-->
    <div class="popup-box success d-none" id="popup-success">
        <div class="popup-icon icon-check cor-branco"></div>
        <h2 class="popup-title">Propriedade ativada com sucesso!</h2>

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

    <!-- Confirmação de Exclusão -->
    <div class="popup-box d-none" id="popup-delete">
        <h2 class="popup-title">Deseja realmente excluir este produto?</h2>
        <p class="popup-text">Esta ação não poderá ser desfeita.</p>

        <div class="popup-actions">
            <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
            <button class="popup-btn fundo-vermelho" type="button" id="confirm-delete">Excluir</button>
        </div>
    </div>

    <!-- Alterar Propriedade -->
    <div class="popup-box v2 d-none" id="popup-prop">
        <h2 class="popup-title">Alterar Propriedade</h2>
        
        <div class="item-box prop-box v2">
            <?php if(!empty($propriedades)): ?>
                <?php foreach($propriedades as $prop): ?>
                    <div class="item item-propriedade fundo-preto v3 <?= $prop['ativo'] ? 'ativo' : '' ?>" 
                        id="prop-<?= $prop['id'] ?>" 
                        data-id="<?= $prop['id'] ?>">
                        
                        <h4 class="item-title">
                            <?= htmlspecialchars($prop['nome_razao']) ?>
                            <?php if ($prop['ativo']): ?>
                                <span class="badge-ativa">Ativa</span>
                            <?php endif; ?>
                        </h4>

                        <div class="item-edit">
                            <button class="edit-btn fundo-azul select-propriedade" type="button">
                                Selecionar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="item-none">Nenhuma propriedade cadastrada.</div>
            <?php endif; ?>
        </div>

        <div class="popup-actions">
            <button class="popup-btn" type="button" onclick="closePopup()">Voltar</button>
            <button class="popup-btn fundo-verde" type="button" id="btn-ativar">Ativar</button>
        </div>
    </div>


</div>