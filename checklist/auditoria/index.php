<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();

$res = $mysqli->query("
    SELECT a.*, u.email
    FROM checklist_auditoria a
    JOIN usuarios u ON u.id = a.usuario_id
    ORDER BY a.criado_em DESC
");
$logs = $res->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Auditoria</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
<h3>🧾 Auditoria de Exclusões</h3>

<table class="table table-bordered">
<thead>
<tr>
<th>ID</th>
<th>Checklist</th>
<th>Usuário</th>
<th>IP</th>
<th>Quando</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $l): ?>
<tr>
<td><?= $l['id'] ?></td>
<td><?= $l['checklist_id'] ?></td>
<td><?= $l['email'] ?></td>
<td><?= $l['ip'] ?></td>
<td><?= $l['criado_em'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>
