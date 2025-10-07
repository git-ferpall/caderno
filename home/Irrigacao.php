<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Caderno de Campo - Irrigação</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>

<body>
  <?php require '../include/loading.php' ?>
  <?php include '../include/popups.php' ?>

  <div id="conteudo">
    <?php include '../include/menu.php' ?>

    <main id="irrigacao" class="sistema">
      <div class="page-title">
        <h2 class="main-title cor-branco">Irrigação</h2>
      </div>

      <div class="sistema-main container">
        <div class="apt-box">
          <form id="form-irrigacao" class="main-form" method="post">

            <!-- Data -->
            <div class="form-campo">
              <label for="data">Data</label>
              <input type="date" class="form-text" id="data" name="data" required>
            </div>

            <!-- Áreas cultivadas -->
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

            <!-- Produtos -->
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
              <input type="text" class="form-text only-num" id="tempo_irrigacao" name="tempo_irrigacao" placeholder="Ex: 2.5" required>
            </div>

            <!-- Volume aplicado -->
            <div class="form-campo">
              <label for="volume_aplicado">Volume aplicado (L ou m³)</label>
              <input type="text" class="form-text only-num" id="volume_aplicado" name="volume_aplicado" placeholder="Ex: 3000" required>
            </div>

            <!-- Observações -->
            <div class="form-campo">
              <label for="obs">Observações</label>
              <textarea id="obs" name="obs" class="form-text form-textarea" placeholder="Insira observações, se houver..."></textarea>
            </div>

            <!-- Botões -->
            <div class="form-submit">
              <button type="button" class="main-btn form-cancel fundo-vermelho" onclick="window.location.href='/home/apontamento.php'">
                <span class="main-btn-text">Cancelar</span>
              </button>

              <button type="submit" class="main-btn fundo-verde">
                <span class="main-btn-text">Salvar</span>
              </button>
            </div>

          </form>
        </div>
      </div>
    </main>

    <?php include '../include/imports.php' ?>
  </div>

  <?php include '../include/footer.php' ?>
  <script src="../js/irrigacao.js"></script>
</body>
</html>
