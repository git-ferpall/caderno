<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inseticida - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Inseticida</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-inseticida" class="main-form">

        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <div class="form-campo">
          <label for="area">Área cultivada</label>
          <select id="area" name="area" class="form-select form-text" required>
            <option value="">Selecione a área</option>
          </select>
        </div>

        <div class="form-campo">
          <label for="inseticida">Inseticida</label>
          <select id="inseticida" name="inseticida" class="form-select form-text" required>
            <option value="">Selecione</option>
            <option value="Lambda-cialotrina">Lambda-cialotrina</option>
            <option value="Imidacloprido">Imidacloprido</option>
            <option value="Clorpirifós">Clorpirifós</option>
            <option value="Abamectina">Abamectina</option>
            <option value="Metomil">Metomil</option>
            <option value="Novaluron">Novaluron</option>
            <option value="Espinosade">Espinosade</option>
          </select>
        </div>

        <div class="form-campo">
          <label for="quantidade">Quantidade Aplicada (L ou mL)</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 2.5" required>
        </div>

        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: 100ml/ha, aplicação em pós-emergência..."></textarea>
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
  <script src="../js/inseticida.js"></script>
</body>
</html>
