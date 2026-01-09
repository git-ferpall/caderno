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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

<h3 class="mb-4">📋 Checklists disponíveis</h3>

<div class="mb-4">
    <a href="../modelos/criar.php" class="btn btn-success">
        ➕ Criar modelo de checklist
    </a>
</div>


<?php if (empty($modelos)): ?>
  <div class="alert alert-warning">
    <strong>Nenhum checklist encontrado.</strong><br>
    Crie um modelo de checklist para começar.
  </div>
<?php endif; ?>


<div class="row">
<?php foreach ($modelos as $m): ?>
  <div class="col-md-4">
    <div class="card mb-3 h-100 shadow-sm">
      <div class="card-body d-flex flex-column">

        <h5 class="card-title">
          <?= htmlspecialchars($m['titulo']) ?>
        </h5>

        <?php if (!empty($m['descricao'])): ?>
          <p class="card-text text-muted">
            <?= nl2br(htmlspecialchars($m['descricao'])) ?>
          </p>
        <?php endif; ?>

        <div class="mt-auto">
          <form method="post" action="criar.php">
            <input type="hidden" name="modelo_id" value="<?= $m['id'] ?>">
            <button class="btn btn-primary w-100">
              Usar este checklist
            </button>
          </form>

          <small class="text-muted d-block mt-2">
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

</body>
</html>
