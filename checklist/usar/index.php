<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

/*
 * 🔐 Valida JWT + acesso ao Caderno de Campo
 * verify_jwt_and_access RETORNA o payload
 */
$payload = verify_jwt_and_access($mysqli);

/*
 * 👤 ID do usuário autenticado (SSO)
 * vem do JWT -> sub
 */
$user_id = (int)($payload['sub'] ?? 0);

if (!$user_id) {
    http_response_code(401);
    die('Usuário não autenticado');
}



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
