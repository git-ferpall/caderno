<?php
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Pega o user_id autenticado
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  $payload = verify_jwt();
  $user_id = $payload['sub'] ?? null;
}

// Buscar máquinas da propriedade ativa
$maquinas = [];
if ($user_id) {
  $sql = "SELECT m.id, m.nome, m.marca, m.tipo 
          FROM maquinas m
          JOIN propriedades p ON m.propriedade_id = p.id
          WHERE p.user_id = ? AND p.ativo = 1
          ORDER BY m.nome ASC";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $maquinas = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Revisão de Máquinas - Caderno de Campo</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    small.aviso { display: block; margin-top: 4px; font-size: 0.9em; }
  </style>
</head>

<body>
  <?php include '../include/loading.php'; ?>
  <?php include '../include/popups.php'; ?>
  <?php include '../include/menu.php'; ?>

  <main class="sistema">
    <div class="page-title">
      <h2 class="main-title cor-branco">Apontamento - Revisão de Máquinas</h2>
    </div>

    <div class="sistema-main container">
      <form id="form-revisao" class="main-form">

        <!-- Data -->
        <div class="form-campo">
          <label for="data">Data da revisão</label>
          <input type="date" id="data" name="data" class="form-text" required>
        </div>

        <!-- Máquina -->
        <div class="form-campo">
          <label for="maquina">Máquina revisada</label>
          <select id="maquina" name="maquina" class="form-select form-text" required>
            <option value="">Selecione a máquina</option>
            <?php foreach ($maquinas as $m): ?>
              <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['nome'] . " (" . $m['marca'] . " - " . $m['tipo'] . ")") ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipo de revisão -->
        <div class="form-campo">
          <label for="tipo">Tipo de revisão</label>
          <input type="text" id="tipo" name="tipo" class="form-text" placeholder="Ex: Troca de óleo, lubrificação, elétrica..." required>
        </div>

        <!-- Custos -->
        <div class="form-campo">
          <label for="custo">Custo da revisão (R$)</label>
          <input type="number" id="custo" name="custo" class="form-text" step="0.01" min="0" placeholder="Ex: 250.00">
          <small class="aviso" id="aviso-status">⚠ Deixe o campo vazio ou zero para manter o apontamento PENDENTE.</small>
        </div>

        <!-- Detalhes -->
        <div class="form-campo">
          <label for="detalhes">Detalhes realizados</label>
          <textarea id="detalhes" name="detalhes" class="form-text form-textarea" placeholder="Descreva os serviços executados"></textarea>
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
  <script src="../js/revisao_maquinas.js"></script>
</body>
</html>
