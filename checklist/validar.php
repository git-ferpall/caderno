<?php
require_once '../config/db.php';

$hash = $_GET['h'] ?? '';

$stmt = $pdo->prepare("
    SELECT id, titulo, fechado_em
    FROM checklists
    WHERE hash_documento = ?
");
$stmt->execute([$hash]);
$chk = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Validação de Checklist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
<?php if ($chk): ?>
  <div class="alert alert-success">
    <h4>✔ Documento autêntico</h4>
    <p><strong>Título:</strong> <?= htmlspecialchars($chk['titulo']) ?></p>
    <p><strong>Fechado em:</strong> <?= date('d/m/Y H:i', strtotime($chk['fechado_em'])) ?></p>
    <p><strong>Hash:</strong><br><code><?= htmlspecialchars($hash) ?></code></p>
  </div>
<?php else: ?>
  <div class="alert alert-danger">
    <h4>✖ Documento inválido</h4>
    <p>O hash informado não foi encontrado.</p>
  </div>
<?php endif; ?>
</div>

</body>
</html>
