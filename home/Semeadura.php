<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Semeadura - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<style>
  .linha {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .lista-areas,
  .lista-produtos {
    flex: 1;
  }
  .lista-areas .form-box-area,
  .lista-produtos .form-box-produto {
    margin-bottom: 5px;
  }
  .linha-quantidade {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .linha-quantidade .form-text {
    width: auto;
  }
  .linha-quantidade input {
    flex: 1;
  }
  .linha-quantidade select {
    width: 160px;
    flex-shrink: 0;
  }
  .remove-btn {
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 6px;
    background: #d9534f;
    color: white;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .remove-btn:hover {
    background: #c9302c;
  }
</style>
<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>
  <div id="conteudo">
    <?php include '../include/menu.php'; ?>

    <main class="sistema">
      <div class="page-title">
        <h2 class="main-title cor-branco">Apontamento - Semeadura</h2>
      </div>

      <div class="sistema-main container">
        <form id="form-semeadura" class="main-form">

          <div class="form-campo">
            <label for="data">Data da semeadura</label>
            <input type="date" id="data" name="data" class="form-text" required>
          </div>

          <div class="form-campo">
            <label>Área / talhão / canteiro / bancada</label>
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

          <div class="form-campo">
            <label>Cultura / produto</label>
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
            <label for="variedade">Variedade / cultivar</label>
            <input type="text" id="variedade" name="variedade" class="form-text" placeholder="Ex: Alface Crespa Verônica">
          </div>

          <div class="form-campo">
            <label for="tipo_semeadura">Tipo de semeadura</label>
            <select id="tipo_semeadura" name="tipo_semeadura" class="form-select form-text" required>
              <option value="">Selecione o tipo</option>
              <option value="Direta">Direta</option>
              <option value="Bandeja">Bandeja</option>
              <option value="Canteiro">Canteiro</option>
              <option value="Replantio">Replantio</option>
            </select>
          </div>

          <div class="form-campo">
            <label>Quantidade semeada</label>
            <div class="linha-quantidade">
              <input type="number" step="0.01" name="quantidade" class="form-text" placeholder="Ex: 300" required>
              <select name="unidade" class="form-select form-text" required>
                <option value="sementes">Sementes</option>
                <option value="kg">Kg</option>
                <option value="bandejas">Bandejas</option>
                <option value="mudas">Mudas</option>
                <option value="sacas">Sacas</option>
              </select>
            </div>
          </div>

          <div class="form-campo">
            <label for="status">Status do manejo</label>
            <select id="status" name="status" class="form-select form-text" required>
              <option value="">Selecione o status</option>
              <option value="concluido">Concluído</option>
              <option value="pendente">Pendente</option>
            </select>
            <small id="aviso-status-semeadura" class="form-hint" style="display:block;margin-top:6px;font-size:0.9em;color:#666;"></small>
          </div>

          <div class="form-campo">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" class="form-text form-textarea"></textarea>
          </div>

          <div class="form-submit">
            <button type="button" class="main-btn fundo-vermelho" onclick="window.location.href='apontamento.php'">
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
  <script src="../js/semeadura.js"></script>
</body>
</html>
