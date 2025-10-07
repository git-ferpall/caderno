<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moscas das Frutas - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha { display: flex; align-items: center; gap: 10px; }
    #lista-areas, #lista-produtos { flex: 1; }
    #lista-areas .form-box-area, #lista-produtos .form-box-produto { margin-bottom: 5px; }
  </style>
</head>
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Moscas das Frutas</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-moscas" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas monitoradas</label>
          <div class="linha">
            <div id="lista-areas" class="lista-areas">
              <div class="form-box form-box-area">
                <select name="area[]" class="form-select form-text area-select" required>
                  <option value="">Selecione a área</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-area" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Produto -->
        <div class="form-campo">
          <label for="produto">Produto cultivado</label>
          <div class="linha">
            <div id="lista-produtos" class="lista-produtos">
              <div class="form-box form-box-produto">
                <select id="produto" name="produto[]" class="form-select form-text produto-select" required>
                  <option value="">Selecione o produto</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-produto" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Tipo de armadilha -->
        <div class="form-campo">
          <label for="armadilha">Tipo de armadilha</label>
          <input type="text" id="armadilha" name="armadilha" class="form-text" placeholder="Ex: McPhail, PET, Jackson" required>
        </div>

        <!-- Atrativo -->
        <div class="form-campo">
          <label for="atrativo">Atrativo utilizado</label>
          <input type="text" id="atrativo" name="atrativo" class="form-text" placeholder="Ex: Torula, BioLure" required>
        </div>

        <!-- Quantidade de armadilhas -->
        <div class="form-campo">
          <label for="qtd_armadilhas">Quantidade de armadilhas</label>
          <input type="number" id="qtd_armadilhas" name="qtd_armadilhas" class="form-text" placeholder="Ex: 5" required>
        </div>

        <!-- Moscas capturadas -->
        <div class="form-campo">
          <label for="qtd_moscas">Moscas capturadas</label>
          <input type="number" id="qtd_moscas" name="qtd_moscas" class="form-text" placeholder="Ex: 10">
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: Armadilhas limpas semanalmente..."></textarea>
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
  <script src="../js/moscas_frutas.js"></script>
</body>
</html>
