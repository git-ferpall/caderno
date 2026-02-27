<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transplantio - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha {
      display: flex;
      align-items: center; /* centraliza verticalmente */
      gap: 10px;           /* espaço entre select e botão */
    }

    #lista-origens,
    #lista-destinos {
      flex: 1; /* ocupa toda a largura disponível */
    }

    #lista-origens .form-box-area,
    #lista-destinos .form-box-area {
      margin-bottom: 5px; /* espaço entre selects empilhados */
    }

  </style>  
</head>
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Transplantio</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-transplantio" class="main-form">

        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <div class="form-campo">
          <label>Áreas de Origem</label>
          <div class="linha">
            <div id="lista-origens">
              <div class="form-box form-box-area">
                <select name="area_origem[]" class="form-select form-text area-origem-select" required>
                  <option value="">Selecione a área de origem</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-origem" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <div class="form-campo">
          <label>Áreas de Destino</label>
          <div class="linha">
            <div id="lista-destinos">
              <div class="form-box form-box-area">
                <select name="area_destino[]" class="form-select form-text area-destino-select" required>
                  <option value="">Selecione a área de destino</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-destino" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>


        <div class="form-campo">
          <label for="produto">Produto</label>
          <select id="produto" name="produto" class="form-select form-text" required>
            <option value="">Selecione o produto</option>
          </select>
        </div>

        <div class="form-campo">
          <label for="quantidade">Quantidade</label>
          <input type="text" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 500 mudas">
        </div>

        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"></textarea>
        </div>

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
  <script src="../js/transplantio.js"></script>
</body>
</html>
