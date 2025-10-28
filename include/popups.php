
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
    <!-- Alerta de Sucesso -->
    <div class="popup-box success d-none" id="popup-ativar">
        <div class="popup-icon icon-check cor-branco"></div>
        <h2 class="popup-title">Propriedade ativada com sucesso!!</h2>

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
    <!-- Solicitar Herbicida -->
    <div class="popup-box v2 d-none" id="popup-solicitar-herbicida">
    <h2 class="popup-title">Solicitar Cadastro de Herbicida</h2>

    <form id="form-solicitar-herbicida" class="main-form">
        <div class="form-campo">
        <label for="herbicida-nome">Nome do Herbicida</label>
        <input type="text" id="herbicida-nome" name="nome" class="form-text" required placeholder="Ex: Glifosato 480 SL">
        </div>

        <div class="form-campo">
        <label for="herbicida-obs">Observações</label>
        <textarea id="herbicida-obs" name="obs" class="form-text form-textarea" placeholder="Informe detalhes como concentração, fabricante etc."></textarea>
        </div>

        <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
        <button class="popup-btn fundo-verde" type="submit">Enviar Solicitação</button>
        </div>
    </form>
    </div>
    <!-- Popup Solicitar Fertilizante -->
    <div class="popup-box v2 d-none" id="popup-solicitar-fertilizante">
    <h2 class="popup-title">Solicitar Cadastro de Fertilizante</h2>

    <form id="form-solicitar-fertilizante" class="main-form">
        <div class="form-campo">
        <label for="fertilizante-nome">Nome do Fertilizante</label>
        <input type="text" id="fertilizante-nome" name="nome" class="form-text" required placeholder="Ex: Ureia 45%">
        </div>

        <div class="form-campo">
        <label for="fertilizante-obs">Observações</label>
        <textarea id="fertilizante-obs" name="obs" class="form-text form-textarea" placeholder="Informe detalhes como concentração, fabricante etc."></textarea>
        </div>

        <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
        <button class="popup-btn fundo-verde" type="submit">Enviar Solicitação</button>
        </div>
    </form>
    </div>
    <!-- Popup Solicitar Fungicida -->
    <!-- Popup Solicitar Fungicida -->
    <div class="popup-box v2 d-none" id="popup-solicitar-fungicida">
    <h2 class="popup-title">Solicitar cadastro de fungicida</h2>

    <form id="form-solicitar-fungicida" class="main-form">
        <div class="form-campo">
        <label for="fungicida-nome">Nome do fungicida</label>
        <input type="text" id="fungicida-nome" name="nome" class="form-text" required placeholder="Ex: Mancozeb 80%">
        </div>

        <div class="form-campo">
        <label for="fungicida-obs">Observações</label>
        <textarea id="fungicida-obs" name="observacao" class="form-text form-textarea"
            placeholder="Ex: concentração, forma de aplicação, restrições..."></textarea>
        </div>

        <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
        <button class="popup-btn fundo-verde" type="submit">Enviar</button>
        </div>
    </form>
    </div>
    <!-- Popup Solicitar Inseticida -->
    <div class="popup-box v2 d-none" id="popup-solicitar-inseticida">
    <h2 class="popup-title">Solicitar cadastro de inseticida</h2>
    <form id="form-solicitar-inseticida" class="main-form">
        <div class="form-campo">
        <label for="inseticida-nome">Nome do inseticida</label>
        <input type="text" id="inseticida-nome" name="nome" class="form-text" required placeholder="Ex: Lambda-cialotrina 50 EC">
        </div>

        <div class="form-campo">
        <label for="inseticida-obs">Observações</label>
        <textarea id="inseticida-obs" name="observacao" class="form-text form-textarea" placeholder="Ex: concentração, forma de aplicação..."></textarea>
        </div>

        <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
        <button class="popup-btn fundo-verde" type="submit">Enviar</button>
        </div>
    </form>
    </div>
    <!-- Popup Detalhes do Manejo -->
    <div class="popup-box v2 d-none" id="popup-detalhe-manejo">
    <h2 class="popup-title">Detalhes do Manejo</h2>

    <div class="main-form" id="manejo-detalhes">
        <div class="form-campo">
        <label>Data:</label>
        <input type="text" id="manejo-data" class="form-text" readonly>
        </div>
        <div class="form-campo">
        <label>Tipo:</label>
        <input type="text" id="manejo-tipo" class="form-text" readonly>
        </div>
        <div class="form-campo">
        <label>Status:</label>
        <input type="text" id="manejo-status" class="form-text" readonly>
        </div>
        <div class="form-campo">
        <label>Áreas:</label>
        <div id="manejo-areas" class="form-text" style="background:#f8f8f8;padding:6px;border-radius:4px;"></div>
        </div>
        <div class="form-campo">
        <label>Produtos:</label>
        <div id="manejo-produtos" class="form-text" style="background:#f8f8f8;padding:6px;border-radius:4px;"></div>
        </div>
        <div class="form-campo">
        <label>Detalhes:</label>
        <div id="manejo-detalhes-extra" class="form-text" style="background:#f8f8f8;padding:6px;border-radius:4px;font-size:0.9rem;"></div>
        </div>
    </div>

    <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Fechar</button>
        <button class="popup-btn fundo-verde" id="btn-marcar-concluido" type="button">Marcar como concluído</button>
    </div>
    </div>

    </div>
    <div id="popup-bancada" class="popup d-none">
        <div class="popup-inner">
            <button class="popup-close" onclick="fecharPopupBancada()">×</button>
            <div id="popup-bancada-conteudo"></div>
        </div>
    </div>
    <!-- 📦 Popup de mover arquivo/pasta -->
    <div id="popup-mover" class="popup-sistema" style="display:none;">
    <div class="popup-box">
        <h3>Mover para outra pasta</h3>
        <p>Selecione a pasta de destino:</p>
        <select id="mover-destino" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;">
        <option value="">Carregando...</option>
        </select>
        <button id="btn-confirmar-mover">Mover</button>
    </div>
    </div>
    <!-- Popup de Upload com Progresso -->
    <div id="uploadPopup" class="upload-popup" style="display:none;">
    <div class="upload-box">
        <h3>⬆️ Enviando arquivos...</h3>
        <div id="uploadLista"></div>
        <p id="uploadResumo" class="progress-text">Preparando...</p>
    </div>
    </div>



   








</div>