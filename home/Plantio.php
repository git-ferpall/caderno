<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plantio - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
  
</head>
<style>
      .linha {
      display: flex;
      align-items: center; /* centraliza verticalmente */
      gap: 10px;           /* espaço entre select e botão */
    }

    .lista-areas,
    .lista-produtos {
      flex: 1; /* ocupa todo o espaço da linha */
    }

    .lista-areas .form-box-area,
    .lista-produtos .form-box-produto {
      margin-bottom: 5px; /* espaço entre selects empilhados */
    }
    .linha-quantidade {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Remove o width 100% herdado */
    .linha-quantidade .form-text {
      width: auto;
    }

    /* Input cresce */
    .linha-quantidade input {
      flex: 1;
    }

    /* Select fica fixo */
    .linha-quantidade select {
      width: 150px;
      flex-shrink: 0;
    }
</style>  
<body>
  <?php include '../include/loading.php'; ?> 
  <?php include '../include/popups.php'; ?>
  <div id="conteudo">
    <?php include '../include/menu.php'; ?>

    <main class="sistema">
      <div class="page-title">
        <h2 class="main-title cor-branco">Apontamento - Plantio</h2>
      </div>

      <div class="sistema-main container">
        <form id="form-plantio" class="main-form">

          <div class="form-campo">
            <label for="data">Data</label>
            <input type="date" id="data" name="data" class="form-text" required>
          </div>

          <!-- ÁREAS -->
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



          <!-- PRODUTOS -->
          <div class="form-campo">
            <label>Produtos cultivados</label>
            <div class="linha">
              <div id="lista-produtos" class="lista-produtos">
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
            <label>Quantidade</label>

            <div class="linha-quantidade">
              <input type="number" step="0.01"
                    name="quantidade"
                    class="form-text"
                    placeholder="Ex: 2000"
                    required>

              <select name="unidade"
                      class="form-select form-text"
                      required>
                <option value="mudas">Mudas</option>
                <option value="kg">Kg</option>
                <option value="caixas">Caixas</option>
                <option value="bandejas">Bandejas</option>
              </select>
            </div>
          </div>

          <div class="form-campo">
            <label for="previsao">Previsão de Colheita (dias)</label>
            <input type="number" id="previsao" name="previsao" class="form-text" placeholder="Ex: 120" required>
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
  </div>
  <?php include '../include/footer.php'; ?>
  <script src="../js/plantio.js"></script>
</body>
</html>
