<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  $payload = verify_jwt();
  $user_id = $payload['sub'] ?? null;
}

// Buscar áreas da propriedade ativa
$areas = [];
if ($user_id) {
  $sql = "SELECT a.id, a.nome, a.tipo 
          FROM areas a
          JOIN propriedades p ON a.propriedade_id = p.id
          WHERE p.user_id = ? AND p.ativo = 1
          ORDER BY a.nome ASC";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $areas = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coleta e Análise - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    small.aviso { display:block; margin-top:4px; font-size:0.9em; }
    .linha { display:flex; align-items:center; gap:10px; }
  </style>
</head>

<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Coleta e Análise</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-coleta" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data da coleta</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas amostradas</label>
          <div class="linha">
            <div id="lista-areas" class="lista-areas">
              <div class="form-box form-box-area">
                <select name="area[]" class="form-select form-text area-select" required>
                  <option value="">Selecione a área</option>
                  <?php foreach ($areas as $a): ?>
                    <option value="<?= $a['id'] ?>">
                      <?= htmlspecialchars($a['nome'] . " (" . $a['tipo'] . ")") ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button class="add-btn add-area" type="button">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Tipo de análise -->
        <div class="form-campo">
          <label for="tipo">Tipo de análise</label>
          <select id="tipo" name="tipo" class="form-select form-text" required>
            <option value="">Selecione</option>
            <option value="solo">Solo</option>
            <option value="foliar">Foliar</option>
            <option value="água">Água</option>
            <option value="resíduo">Resíduo</option>
          </select>
        </div>

        <!-- Laboratório -->
        <div class="form-campo">
          <label for="laboratorio">Laboratório responsável</label>
          <input type="text" id="laboratorio" name="laboratorio" class="form-text" placeholder="Ex: AgroLab, TecSolo...">
        </div>

        <!-- Responsável pela coleta -->
        <div class="form-campo">
          <label for="responsavel">Responsável pela coleta</label>
          <input type="text" id="responsavel" name="responsavel" class="form-text" placeholder="Nome do técnico ou funcionário">
        </div>

        <!-- Resultado -->
        <div class="form-campo">
          <label for="resultado">Resultado (opcional)</label>
          <textarea id="resultado" name="resultado" class="form-text form-textarea" placeholder="Resumo do laudo ou valores principais"></textarea>
          <small class="aviso" id="aviso-status">⚠ Se o resultado for informado, o status será CONCLUÍDO.</small>
        </div>

        <!-- Observações -->
        <div class="form-campo">
          <label for="obs">Observações adicionais</label>
          <textarea id="obs" name="obs" class="form-text form-textarea" placeholder="Anotações gerais"></textarea>
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
  <script src="../js/coleta_analise.js"></script>
</body>
</html>
