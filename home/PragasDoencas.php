<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pragas e Doenças - Caderno de Campo</title>
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
      <h2 class="main-title cor-branco">Apontamento - Pragas e Doenças</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-pragas" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data da Observação</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas afetadas</label>
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
          <label>Produtos cultivados</label>
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

        <!-- Praga -->
        <div class="form-campo">
          <label for="praga">Praga observada</label>
          <input type="text" id="praga" name="praga" class="form-text" placeholder="Ex: Mosca-branca, Ácaro, etc.">
        </div>

        <!-- Doença -->
        <div class="form-campo">
          <label for="doenca">Doença observada</label>
          <input type="text" id="doenca" name="doenca" class="form-text" placeholder="Ex: Míldio, Ferrugem, etc.">
        </div>

        <!-- Intensidade -->
        <div class="form-campo">
          <label for="intensidade">Intensidade</label>
          <select id="intensidade" name="intensidade" class="form-select form-text">
            <option value="">Selecione</option>
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
          </select>
        </div>

        <!-- Ação corretiva -->
        <div class="form-campo">
          <label for="acao_corretiva">Ação corretiva</label>
          <textarea id="acao_corretiva" name="acao_corretiva" class="form-text form-textarea"
            placeholder="Ex: Aplicação de fungicida X, controle biológico..."></textarea>
        </div>

        <!-- Aviso status -->
        <small id="aviso-status" style="display:block;margin-top:-5px;font-size:0.9em;color:orange;">
          ⚠ Deixe o campo de ação corretiva vazio para manter o status PENDENTE.
        </small>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações gerais</label>
          <textarea id="obs" name="obs" class="form-text form-textarea" placeholder="Anotações adicionais..."></textarea>
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
  <script src="../js/pragas_doencas.js"></script>
</body>
</html>
