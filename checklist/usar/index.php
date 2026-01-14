<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/*
 * 🔒 Garante login:
 * - se não estiver logado → redirect
 * - se estiver logado → retorna JWT (claims)
 */
$user = require_login();

/* 👤 ID do usuário autenticado */
$user_id = (int) $user->sub;


/* 🔒 BASE DO SISTEMA */
define('APP_PATH', realpath(__DIR__ . '/../../'));

/* 🔎 Buscar modelos disponíveis */
$sql = "
    SELECT
        id,
        titulo,
        descricao,
        criado_por,
        publico,
        criado_em
    FROM checklist_modelos
    WHERE publico = 1 OR criado_por = ?
    ORDER BY titulo
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$modelos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Checklists</title>
  <base href="/">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/img/logo-icon.png">
  <link rel="stylesheet" href="/css/style.css">
  <style>
    .page-content {
            margin-top: 80px;
        }
  </style>
</head>

<body class="bg-light">
    <?php require APP_PATH . '/include/loading.php'; ?>
    <?php require APP_PATH . '/include/popups.php'; ?>
    <div id="conteudo">
      <?php require APP_PATH . '/include/menu.php'; ?>
      <div class="container py-4 page-content">

          <!-- TÍTULO -->
          <h3 class="mb-4">📋 Checklists disponíveis</h3>

          <!-- BOTÃO CRIAR MODELO -->
          <div class="text-start">
                <a href="/checklist/modelos/criar.php" class="btn btn-success mb-3" style="background-color:#E95D24; color:#fff;">
                    ➕ Novo modelo
                </a>
          </div>

          <!-- ALERTA: SEM MODELOS -->
          <?php if (empty($modelos)): ?>
              <div class="alert alert-warning">
                  <strong>Nenhum checklist encontrado.</strong><br>
                  Crie um modelo de checklist para começar.
              </div>
          <?php endif; ?>

          <!-- LISTAGEM DE MODELOS -->
          <div class="row">
              <?php foreach ($modelos as $m): ?>
                  <div class="col-md-4 mb-3">
                      <div class="card h-100 shadow-sm">
                          <div class="card-body d-flex flex-column">

                              <!-- TÍTULO -->
                              <h5 class="card-title">
                                  <?= htmlspecialchars($m['titulo']) ?>
                              </h5>

                              <!-- DESCRIÇÃO -->
                              <?php if (!empty($m['descricao'])): ?>
                                  <p class="card-text text-muted">
                                      <?= nl2br(htmlspecialchars($m['descricao'])) ?>
                                  </p>
                              <?php endif; ?>

                              <!-- AÇÕES -->
                              <div class="mt-auto">

                                  <form method="post" action="/checklist/usar/criar.php" novalidadte>
                                      <input type="hidden" name="modelo_id" value="<?= $m['id'] ?>">

                                      <button type="submit" class="btn btn-primary w-100">
                                          Usar este checklist
                                      </button>
                                  </form>

                                  <small class="text-muted d-block mt-2 text-center">
                                      <?= $m['criado_por'] == $user_id
                                          ? 'Modelo pessoal'
                                          : 'Modelo padrão do sistema' ?>
                                  </small>

                              </div>

                          </div>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      </div>
    </div>  
    <?php require APP_PATH . '/include/footer.php'; ?>
  <!--<script src="/js/jquery.js"></script>
  <script src="/js/main.js"></script>-->
  <script src="/js/popups.js"></script>
  <script src="/js/script.js"></script>                           
</body>

</html>
