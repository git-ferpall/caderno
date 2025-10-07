<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Controle de Água - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    #lista-fontes {
      flex: 1;
    }
    #lista-fontes .form-box-fonte {
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
      <h2 class="main-title cor-branco">Apontamento - Controle de Água</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-controle-agua" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Tipo de fonte -->
        <div class="form-campo">
          <label>Fonte de captação</label>
          <div class="linha">
            <div id="lista-fontes" class="lista-fontes">
              <div class="form-box form-box-fonte">
                <select name="fonte[]" class="form-select form-text fonte-select" required>
                  <option value="">Selecione a fonte</option>
                  <option value="Poço">Poço</option>
                  <option value="Rio">Rio</option>
                  <option value="Lago">Lago</option>
                  <option value="Rede pública">Rede pública</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
            </div>
            <button class="add-btn add-fonte" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Volume -->
        <div class="form-campo">
          <label for="volume">Volume captado (L ou m³)</label>
          <input type="number" id="volume" name="volume" class="form-text" step="0.01" placeholder="Ex: 15000" required>
        </div>

        <!-- Finalidade -->
        <div class="form-campo">
          <label for="finalidade">Finalidade</label>
          <select id="finalidade" name="finalidade" class="form-select form-text" required>
            <option value="">Selecione a finalidade</option>
            <option value="Irrigação">Irrigação</option>
            <option value="Limpeza">Limpeza</option>
            <option value="Consumo animal">Consumo animal</option>
            <option value="Consumo humano">Consumo humano</option>
            <option value="Outro">Outro</option>
          </select>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: Captação reduzida devido à estiagem..."></textarea>
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
  <script src="../js/controle_agua.js"></script>
</body>
</html>
