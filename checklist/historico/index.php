<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$status = $_GET['status'] ?? 'todos';

$where = '';
if ($status === 'aberto') {
    $where = 'AND c.concluido = 0';
} elseif ($status === 'finalizado') {
    $where = 'AND c.concluido = 1';
}

$stmt = $mysqli->prepare("
    SELECT
        c.id,
        c.titulo,
        c.criado_em,
        c.fechado_em,
        c.concluido,
        c.hash_documento,
        m.publico AS modelo_publico
    FROM checklists c
    JOIN checklist_modelos m ON m.id = c.modelo_id
    WHERE c.user_id = ?
    $where
    ORDER BY c.criado_em DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$checklists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Histórico de Checklists</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
<h3>🧾 Histórico de Checklists</h3>

<div class="btn-group mb-3">
    <a href="?status=todos" class="btn btn-outline-primary <?= $status==='todos'?'active':'' ?>">Todos</a>
    <a href="?status=aberto" class="btn btn-outline-primary <?= $status==='aberto'?'active':'' ?>">Abertos</a>
    <a href="?status=finalizado" class="btn btn-outline-primary <?= $status==='finalizado'?'active':'' ?>">Finalizados</a>
</div>
<div class="mb-3 position-relative">
    <input
        type="text"
        id="buscaChecklist"
        class="form-control"
        placeholder="🔍 Buscar checklist pelo título..."
        autocomplete="off"
    >
    <div id="resultadoBusca"
         class="list-group position-absolute w-100"
         style="z-index:1000; display:none;">
    </div>
</div>


<table class="table table-bordered table-hover">
<thead>
<tr>
    <th>ID</th>
    <th>Título</th>
    <th>Status</th>
    <th>Criado</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>

<?php foreach ($checklists as $c): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['titulo']) ?></td>
    <td>
        <?= $c['concluido'] ? '✔ Finalizado' : '⏳ Aberto' ?>
    </td>
    <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
    <td class="d-flex gap-1">

        <?php if ($c['concluido']): ?>
            <a target="_blank"
               href="/checklist/pdf/gerar.php?id=<?= $c['id'] ?>"
               class="btn btn-sm btn-outline-success">📄 PDF</a>

            <?php if ($c['hash_documento']): ?>
                <a target="_blank"
                   href="/checklist/validar/index.php?hash=<?= $c['hash_documento'] ?>"
                   class="btn btn-sm btn-outline-secondary">🔗 Público</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$c['modelo_publico']): ?>
            <a href="deletar.php?id=<?= $c['id'] ?>"
               onclick="return confirm('Excluir checklist e toda a mídia?')"
               class="btn btn-sm btn-outline-danger">🗑</a>
        <?php endif; ?>

    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</body>
</html>
