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
  <title>Apontamento Personalizado - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .linha { display: flex; align-items: center; gap: 10px; }
    #lista-areas { flex: 1; }
    .form-box-area { margin-bottom: 5px; }
    #aviso-status { display: block; font-size: 0.9em; margin-top: 6px; color: orange; }
  </style>
</head>

<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Personalizado</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-personalizado" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data do apontamento</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Áreas -->
        <div class="form-campo">
          <label>Áreas relacionadas</label>
          <div class="linha">
            <div id="lista-areas" class="lista-areas">
              <div class="form-box form-box-area">
                <select name="area[]" class="form-select form-text area-select" required>
                  <option value="">Selecione a área</option>
                  <?php foreach ($areas as $a): ?>
                    <option value="<?= htmlspecialchars($a['id']) ?>">
                      <?= htmlspecialchars($a['nome']) ?> (<?= htmlspecialchars($a['tipo']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button class="add-btn add-area" type="button" title="Adicionar nova área">
              <div class="btn-icon icon-plus cor-branco"></div>
            </button>
          </div>
        </div>

        <!-- Título -->
        <div class="form-campo">
          <label for="titulo">Título do apontamento</label>
          <input type="text" id="titulo" name="titulo" class="form-text" placeholder="Ex: Verificação de irrigação, Reunião técnica..." required>
        </div>

        <!-- Descrição -->
        <div class="form-campo">
          <label for="descricao">Descrição detalhada</label>
          <textarea id="descricao" name="descricao" class="form-text form-textarea" placeholder="Descreva as ações ou observações..."></textarea>
        </div>

        <!-- Responsável -->
        <div class="form-campo">
          <label for="responsavel">Responsável</label>
          <input type="text" id="responsavel" name="responsavel" class="form-text" placeholder="Nome do responsável">
        </div>

        <!-- Conclusão -->
        <div class="form-campo">
          <label for="conclusao">Conclusão (opcional)</label>
          <textarea id="conclusao" name="conclusao" class="form-text form-textarea" placeholder="Resumo final ou resultado da atividade"></textarea>
          <small id="aviso-status">⚠ Se a conclusão for preenchida, o status será CONCLUÍDO.</small>
        </div>

        <!-- Botões -->
        <div class="form-submit">
          <button type="reset" class="main-btn fundo-vermelho"><span>Cancelar</span></button>
          <button type="submit" class="main-btn fundo-verde"><span>Salvar</span></button>
        </div>
      </form>
    </div>
  </main>

  <?php include '../include/imports.php'; ?>
  <?php include '../include/footer.php'; ?>
  <script src="../js/personalizado.js"></script>
</body>
</html>
