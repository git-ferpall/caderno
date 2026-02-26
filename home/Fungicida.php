<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fungicida - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    #lista-areas {
      flex: 1;
    }
    #lista-areas .form-box-area {
      margin-bottom: 5px;
    }
  </style>
</head>
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Fungicida</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-fungicida" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas cultivadas</label>
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

        <!-- Fungicida -->
        <div class="form-campo">
          <label for="fungicida">Fungicida</label>
          <select id="fungicida" name="fungicida" class="form-select form-text" required>
            <option value="">Selecione o fungicida</option>
          </select>

          <input type="text" id="fungicida_outro" name="fungicida_outro"
            class="form-text" placeholder="Digite o nome do fungicida"
            style="display:none; margin-top:8px;">
        </div>

        <!--<button type="button" class="main-btn fundo-laranja" onclick="abrirPopup('popup-solicitar-fungicida')" style="margin-top:8px">
          Solicitar cadastro de novo fungicida
        </button> -->

        <!-- Quantidade -->
        <div class="form-campo">
          <label for="quantidade">Quantidade Aplicada (L)</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 5" required>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: 200ml/ha, aplicação preventiva..."></textarea>
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
  <script src="../js/fungicida.js"></script>
</body>
</html>
