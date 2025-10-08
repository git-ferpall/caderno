<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manejo Integrado - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
  <style>
    .linha { display: flex; align-items: center; gap: 10px; }
    #lista-areas, #lista-produtos { flex: 1; }
    .form-box-area, .form-box-produto { margin-bottom: 5px; }
  </style>
</head>

<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Manejo Integrado</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-manejo" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data do Manejo</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas envolvidas</label>
          <div class="linha">
            <div id="lista-areas">
              <div class="form-box form-box-area">
                <select name="area[]" class="form-select form-text area-select" required>
                  <option value="">Selecione a área</option>
                </select>
              </div>
            </div>
            <button type="button" class="add-btn add-area">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Produtos -->
        <div class="form-campo">
          <label>Produtos envolvidos</label>
          <div class="linha">
            <div id="lista-produtos">
              <div class="form-box form-box-produto">
                <select name="produto[]" class="form-select form-text produto-select" required>
                  <option value="">Selecione o produto</option>
                </select>
              </div>
            </div>
            <button type="button" class="add-btn add-produto">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Método de controle -->
        <div class="form-campo">
          <label for="metodo_controle">Método de Controle</label>
          <select id="metodo_controle" name="metodo_controle" class="form-select form-text">
            <option value="">Selecione</option>
            <option value="biologico">Biológico</option>
            <option value="quimico">Químico</option>
            <option value="cultural">Cultural</option>
            <option value="fisico">Físico</option>
            <option value="outro">Outro</option>
          </select>
        </div>

        <!-- Agente biológico -->
        <div class="form-campo">
          <label for="agente_biologico">Agente Biológico</label>
          <input type="text" id="agente_biologico" name="agente_biologico" class="form-text"
                 placeholder="Ex: Trichogramma pretiosum">
        </div>

        <!-- Produto utilizado -->
        <div class="form-campo">
          <label for="produto_utilizado">Produto Utilizado</label>
          <input type="text" id="produto_utilizado" name="produto_utilizado" class="form-text"
                 placeholder="Ex: Neem, Bacillus thuringiensis">
        </div>

        <!-- Equipamento -->
        <div class="form-campo">
          <label for="equipamento">Equipamento</label>
          <input type="text" id="equipamento" name="equipamento" class="form-text"
                 placeholder="Ex: Pulverizador costal, drone...">
        </div>

        <!-- Eficácia -->
        <div class="form-campo">
          <label for="eficacia">Eficácia</label>
          <select id="eficacia" name="eficacia" class="form-select form-text">
            <option value="">Selecione</option>
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
          </select>
        </div>

        <!-- Ação corretiva -->
        <div class="form-campo">
          <label for="acao_corretiva">Ação Corretiva</label>
          <textarea id="acao_corretiva" name="acao_corretiva" class="form-text form-textarea"
                    placeholder="Ex: Ajuste na dosagem, repetição de aplicação..."></textarea>
          <small id="aviso-status" style="display:block;margin-top:4px;font-size:0.9em;color:orange;">
            ⚠ Deixe o campo de ação corretiva vazio para manter o status PENDENTE.
          </small>
        </div>

        <!-- Responsável -->
        <div class="form-campo">
          <label for="responsavel">Responsável Técnico</label>
          <input type="text" id="responsavel" name="responsavel" class="form-text"
                 placeholder="Nome do responsável pelo manejo">
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações Gerais</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
                    placeholder="Anotações adicionais..."></textarea>
        </div>

        <!-- Botões -->
        <div class="form-submit">
          <button type="reset" class="main-btn fundo-vermelho">
            <span class="main-btn-text">Cancelar</span>
          </button>
          <button type="submit" class="main-btn fundo-verde">
            <span class="main-btn-text">Salvar</span>
          </button>
        </div>

      </form>
    </div>
  </main>

  <?php include '../include/imports.php'; ?>
  <?php include '../include/footer.php'; ?>
  <script src="../js/manejo_integrado.js"></script>
</body>
</html>
