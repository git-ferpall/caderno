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
  <style>
    .linha {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    #lista-areas, 
    #lista-produtos {
      flex: 1;
    }

    #lista-areas .form-box-area,
    #lista-produtos .form-box-produto {
      margin-bottom: 5px;
    }
    .linha-quantidade {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* remove width 100% herdado */
    .linha-quantidade .form-text {
      width: auto;
    }

    /* input cresce */
    .linha-quantidade input {
      flex: 1;
    }

    /* select fixo */
    .linha-quantidade select {
      width: 150px;
      flex-shrink: 0;
    }

  </style>  
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
          <label>Áreas cultivadas</label>
          <div class="linha">
            <div id="lista-areas">
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

        <div class="form-campo">
          <label>Produtos colhidos</label>
          <div class="linha">
            <div id="lista-produtos">
              <div class="form-box form-box-produto">
                <select name="produto[]" class="form-select form-text produto-select" required>
                  <option value="">Selecione o produto</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-produto" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>


        <div class="form-campo">
          <label>Quantidade Colhida</label>

          <div class="linha-quantidade">
            <input type="number" step="0.01"
                  name="quantidade"
                  class="form-text"
                  placeholder="Ex: 1500"
                  required>

            <select name="unidade"
                    class="form-select form-text"
                    required>
              <option value="kg">Kg</option>
              <option value="caixas">Caixas</option>
              <option value="sacas">Sacas</option>
              <option value="bandejas">Bandejas</option>
            </select>
          </div>
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
  <script src="../js/colheita.js"></script>
</body>
</html>
