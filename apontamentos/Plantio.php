<?php
require_once __DIR__ . '/../configuracao/protect.php';
$propriedade_id = $_SESSION['propriedade_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Caderno de Campo - Plantio</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Plantio</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-plantio" class="main-form" action="../apontamentos/funcoes/salvar_plantio.php" method="post">
        
        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Área cultivada -->
        <div class="form-campo">
          <label for="area">Área cultivada</label>
          <select id="area" name="area" class="form-select" required>
            <option value="">Selecione...</option>
          </select>
        </div>

        <!-- Produto cultivado -->
        <div class="form-campo">
          <label for="produto">Produto cultivado</label>
          <select id="produto" name="produto" class="form-select" required>
            <option value="">Selecione...</option>
          </select>
        </div>

        <!-- Quantidade -->
        <div class="form-campo">
          <label for="quantidade">Quantidade</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" required>
        </div>

        <!-- Previsão colheita -->
        <div class="form-campo">
          <label for="previsao">Previsão de colheita (dias)</label>
          <input type="number" id="previsao" name="previsao" class="form-text" required>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"></textarea>
        </div>

        <!-- Botões -->
        <div class="form-submit">
          <button type="reset" class="main-btn fundo-vermelho">Cancelar</button>
          <button type="submit" class="main-btn fundo-verde">Salvar</button>
        </div>
      </form>
    </div>
  </main>

  <script src="../apontamentos/js/plantio.js"></script>
</body>
</html>
