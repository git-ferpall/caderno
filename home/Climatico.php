<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Climático - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Registro Climático</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-clima" class="main-form">

        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <div class="form-campo">
          <label for="tipo">Tipo de Registro</label>
          <select id="tipo" name="tipo" class="form-select form-text" required>
            <option value="">Selecione</option>
            <option value="chuva">Chuva</option>
            <option value="temperatura">Temperatura</option>
            <option value="umidade">Umidade</option>
            <option value="geada">Geada</option>
            <option value="vento">Vento</option>
            <option value="granizo">Granizo</option>
          </select>
        </div>

        <div class="form-campo">
          <label for="valor">Valor</label>
          <input type="text" id="valor" name="valor" class="form-text" placeholder="Ex: 20mm, 32°C, 70%">
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
  <script src="../js/climatico.js"></script>
</body>
</html>
