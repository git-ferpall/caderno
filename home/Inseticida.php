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
      <h2 class="main-title cor-branco">Apontamento - Inseticida</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-inseticida" class="main-form">

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

        <!-- Inseticida -->
        <div class="form-campo">
          <label for="inseticida">Inseticida</label>
          <select id="inseticida" name="inseticida" class="form-select form-text" required>
            <option value="">Selecione o inseticida</option>
          </select>
        </div>
        <button type="button" class="main-btn fundo-laranja" onclick="abrirPopup('popup-solicitar-inseticida')" style="margin-top:8px">
          Solicitar cadastro de novo inseticida
        </button>

        <!-- Quantidade -->
        <div class="form-campo">
          <label for="quantidade">Quantidade Aplicada (L)</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 5" required>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: 50ml/ha, aplicação em pós-emergência..."></textarea>
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

  <!-- Popup Solicitar Inseticida -->
  <div class="popup-box v2 d-none" id="popup-solicitar-inseticida">
    <h2 class="popup-title">Solicitar cadastro de inseticida</h2>
    <form id="form-solicitar-inseticida" class="main-form">
      <div class="form-campo">
        <label for="inseticida-nome">Nome do inseticida</label>
        <input type="text" id="inseticida-nome" name="nome" class="form-text" required placeholder="Ex: Lambda-cialotrina">
      </div>
      <div class="form-campo">
        <label for="inseticida-obs">Observações</label>
        <textarea id="inseticida-obs" name="observacao" class="form-text form-textarea"
          placeholder="Ex: concentração, modo de aplicação, restrições..."></textarea>
      </div>
      <div class="popup-actions">
        <button class="popup-btn fundo-cinza-b cor-preto" type="button" onclick="closePopup()">Cancelar</button>
        <button class="popup-btn fundo-verde" type="submit">Enviar</button>
      </div>
    </form>
  </div>

  <?php include '../include/imports.php'; ?>
  <?php include '../include/footer.php'; ?>
  <script src="../js/inseticida.js"></script>
</body>
</html>
