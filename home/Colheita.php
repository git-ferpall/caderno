<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Colheita - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Colheita</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-colheita" class="main-form">

        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <div class="form-campo">
          <label for="area">Área cultivada</label>
          <div class="form-box form-box-area">
            <select id="area" name="area" class="form-select form-text" required>
              <option value="">Selecione a área</option>
            </select>
            <button class="add-btn add-area" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <div class="form-campo">
          <label for="produto">Produto colhido</label>
          <div class="form-box form-box-produto">
            <select id="produto" name="produto" class="form-select form-text" required>
              <option value="">Selecione o produto</option>
            </select>
            <button class="add-btn add-produto" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <div class="form-campo">
          <label for="quantidade">Quantidade Colhida</label>
          <small style="color:#d9534f">(Para deixar com status pendente, deixe em branco)</small>
          <input type="text" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 1500 kg" required>
        </div>

        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"></textarea>
        </div>

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
  <script src="../js/colheita.js"></script>
</body>
</html>
