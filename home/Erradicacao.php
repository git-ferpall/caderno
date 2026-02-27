<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Erradicação - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
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
      <h2 class="main-title cor-branco">Apontamento - Erradicação</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-erradicacao" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data da erradicação</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas erradicadas</label>
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

        <!-- Motivo da erradicação -->
        <div class="form-campo">
          <label for="motivo">Motivo da erradicação</label>
          <input type="text" id="motivo" name="motivo" class="form-text" placeholder="Ex: Doença, replantio, baixa produtividade..." required>
        </div>

        <!-- Método utilizado -->
        <div class="form-campo">
          <label for="metodo">Método utilizado</label>
          <input type="text" id="metodo" name="metodo" class="form-text" placeholder="Ex: Corte, arranquio, queima controlada...">
        </div>

        <!-- Quantidade de plantas erradicadas -->
        <div class="form-campo">
          <label for="quantidade">Quantidade de plantas erradicadas</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 120" step="1" min="0">
          <small id="aviso-status" style="display:block;margin-top:4px;font-size:0.9em;color:orange;">
            ⚠ Deixe o campo vazio para manter o apontamento PENDENTE.
          </small>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea" placeholder="Anotações adicionais..."></textarea>
        </div>

        <!-- Botões -->
        <div class="form-submit">
          <button type="button" 
                  class="main-btn fundo-vermelho"
                  onclick="window.location.href='apontamento.php'">
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
  <script src="../js/erradicacao.js"></script>
</body>
</html>
