<?php
require_once '../../config/db.php';
session_start();

$user_id = $_SESSION['user_id'];

$sql = "
SELECT *
FROM checklist_modelos
WHERE publico = 1 OR criado_por = ?
ORDER BY titulo
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Usar Checklist</h3>

<div class="row">
<?php foreach ($modelos as $m): ?>
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-body">
        <h5><?= htmlspecialchars($m['titulo']) ?></h5>
        <p><?= nl2br(htmlspecialchars($m['descricao'])) ?></p>

        <form method="post" action="criar.php">
          <input type="hidden" name="modelo_id" value="<?= $m['id'] ?>">
          <button class="btn btn-primary w-100">
            Usar este checklist
          </button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach ?>
</div>
