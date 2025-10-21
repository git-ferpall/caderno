<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aplicação de Fertilizante - Caderno de Campo</title>
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
      <h2 class="main-title cor-branco">Apontamento - Fertilizante</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-fertilizante" class="main-form">

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

        <!-- Fertilizante -->
        <div class="form-campo">
          <label for="fertilizante">Fertilizante</label>
          <select id="fertilizante" name="fertilizante" class="form-select form-text" required>
            <option value="">Selecione o fertilizante</option>
            <option value="outro">Outro (digitar manualmente)</option>
          </select>
          <input type="text" id="fertilizante_outro" name="fertilizante_outro"
            class="form-text" placeholder="Digite o nome do fertilizante"
            style="display:none; margin-top:8px;">
        </div>

        <!-- Botão para solicitar novo fertilizante 
        <button type="button" class="main-btn fundo-laranja" 
                onclick="abrirPopup('popup-solicitar-fertilizante')" 
                style="margin-top:8px">
          Solicitar cadastro de novo fertilizante
        </button>-->


        <div class="form-campo">
          <label for="quantidade">Quantidade (Kg)</label>
          <input type="number" id="quantidade" name="quantidade" class="form-text" placeholder="Ex: 100" required>
        </div>

        <div class="form-campo">
          <label for="obs">Observações</label>
          <textarea id="obs" name="obs" class="form-text form-textarea"
            placeholder="Ex: 100 kg/ha, aplicação foliar, 2x ao mês..."></textarea>
        </div>

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
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const inputOutro = document.getElementById('fertilizante_outro');
      const select = document.getElementById('fertilizante');

      // Evita erro se ainda não existir
      if (!inputOutro || !select) return;

      // Oculta inicialmente
      inputOutro.style.display = 'none';

      // Mostra/oculta conforme seleção
      select.addEventListener('change', () => {
        const outro = select.value === 'outro';
        inputOutro.style.display = outro ? 'block' : 'none';
        inputOutro.required = outro;
      });
    });
  </script>
</body>
<script src="../js/fertilizante.js"></script>
</html>
