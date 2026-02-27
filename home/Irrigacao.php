<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Irrigação - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    #lista-areas, #lista-produtos {
      flex: 1;
    }
    #lista-areas .form-box-area,
    #lista-produtos .form-box-produto {
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
      <h2 class="main-title cor-branco">Apontamento - Irrigação</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-irrigacao" class="main-form">

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

        <!-- Produto cultivado -->
        <div class="form-campo">
          <label>Produto cultivado</label>
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

        <!-- Tempo de irrigação -->
        <div class="form-campo">
          <label for="tempo_irrigacao">Tempo de irrigação (horas)</label>
          <input type="number" id="tempo_irrigacao" name="tempo_irrigacao" class="form-text" placeholder="Ex: 2.5" step="0.01" required>
        </div>

        <!-- Volume aplicado -->
        <div class="form-campo">
          <label for="volume_aplicado">Volume aplicado (L ou m³)</label>
          <input type="number" id="volume_aplicado" name="volume_aplicado" class="form-text" placeholder="Ex: 3000" step="0.01" required>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: Irrigação realizada após adubação..."></textarea>
        </div>

        <!-- Botões -->
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
  <script src="../js/irrigacao.js"></script>
</body>
</html>
