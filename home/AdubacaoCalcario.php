<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Adubação (Calcário / Gesso) - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
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
</style>  
</head>

<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>

  <div id="conteudo">
    <?php include '../include/menu.php'; ?>

    <main class="sistema">
      <div class="page-title">
        <h2 class="main-title cor-branco">Apontamento - Adubação (Calcário / Gesso)</h2>
      </div>

      <div class="sistema-main container">
        <form id="form-adubacao-calcario" class="main-form">

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

        <!-- Produto Cultivado -->
        <div class="form-campo">
        <label for="produto">Produto cultivado</label>
        <div class="form-box form-box-produto">
            <select id="produto" name="produto[]" class="form-select form-text" multiple required>
            <option value="">Selecione o(s) produto(s)</option>
            </select>
            <button class="add-btn add-produto" type="button">
            <div class="btn-icon icon-plus cor-branco"></div>
            </button>
        </div>
        </div>


          <!-- Tipo (Calcário, Gesso, Mistura) -->
          <div class="form-campo">
            <label for="tipo">Tipo de produto</label>
            <input type="text" id="tipo" name="tipo" class="form-text" placeholder="Ex: Calcário dolomítico, Gesso agrícola, Mistura, etc." required>
          </div>

          <!-- Quantidade -->
          <div class="form-campo">
            <label for="quantidade">Quantidade (t/ha)</label>
            <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 2.5" step="0.01" required>
          </div>

          <!-- PRNT -->
          <div class="form-campo">
            <label for="prnt">PRNT (%)</label>
            <input type="number" id="prnt" name="prnt" class="form-text" placeholder="Ex: 90" required>
          </div>

          <!-- Forma de aplicação -->
          <div class="form-campo">
            <label class="item-label" for="forma_aplicacao">Forma de aplicação</label>
            <div class="form-radio-box" id="forma_aplicacao">
              <label class="form-radio v2">
                <input type="radio" name="forma_aplicacao" value="Cobertura" checked/>
                Cobertura
              </label>
              <label class="form-radio v2">
                <input type="radio" name="forma_aplicacao" value="Incorporado" />
                Incorporado
              </label>
            </div>
          </div>

          <!-- Nº de referência da amostra -->
          <div class="form-campo">
            <label for="n_referencia">N° de referência da amostra</label>
            <input type="text" id="n_referencia" name="n_referencia" class="form-text" placeholder="Ex: AM-2025-001">
          </div>

          <!-- Observações -->
          <div class="form-campo">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" class="form-text form-textarea" placeholder="Insira observações relevantes (ex: aplicação realizada após preparo de solo)..."></textarea>
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
  </div>

  <?php include '../include/footer.php'; ?>
  <script src="../js/adubacao_calcario.js"></script>
</body>
</html>
