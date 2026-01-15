<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

/* 🔎 Filtro */
$filtro = $_GET['status'] ?? 'todos';

/* 🔎 SQL base */
$sql = "
    SELECT
        c.id,
        c.titulo,
        c.concluido,
        c.criado_em,
        c.fechado_em,
        c.hash_documento,
        m.publico AS modelo_publico
    FROM checklists c
    INNER JOIN checklist_modelos m ON m.id = c.modelo_id
    WHERE c.user_id = ?
";

if ($filtro === 'abertos') {
    $sql .= " AND c.concluido = 0";
} elseif ($filtro === 'finalizados') {
    $sql .= " AND c.concluido = 1";
}

$sql .= " ORDER BY c.criado_em DESC";

$stmt = $mysqli->prepare($sql);
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
<base href="/">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="/img/logo-icon.png">
<link rel="stylesheet" href="/css/style.css">
<style>
.page-content { margin-top: 80px; }
main.sistema {
    background: rgba(255,255,255,.9);
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 12px 30px rgba(0,0,0,.15);
}
</style>
</head>

<body class="bg-light">

<?php require __DIR__ . '/../../include/loading.php'; ?>
<?php require __DIR__ . '/../../include/popups.php'; ?>

<div id="conteudo">
<?php require __DIR__ . '/../../include/menu.php'; ?>

<div class="container py-4 page-content">
<main class="sistema">

<h3 class="mb-3">🧾 Histórico de Checklists</h3>

<!-- FILTROS -->
<div class="d-flex gap-2 mb-3">
    <a href="?status=todos" class="btn btn-outline-secondary <?= $filtro==='todos'?'active':'' ?>">Todos</a>
    <a href="?status=abertos" class="btn btn-outline-warning <?= $filtro==='abertos'?'active':'' ?>">Abertos</a>
    <a href="?status=finalizados" class="btn btn-outline-success <?= $filtro==='finalizados'?'active':'' ?>">Finalizados</a>
</div>

<!-- BUSCA -->
<input
    type="text"
    id="buscaChecklist"
    class="form-control mb-3"
    placeholder="🔍 Buscar checklist pelo título..."
>

<table class="table table-striped" id="tabelaChecklists">
<thead>
<tr>
    <th>Título</th>
    <th>Status</th>
    <th>Criado</th>
    <th>Ações</th>
</tr>
</thead>

<tbody>
<?php foreach ($checklists as $c): ?>
<tr>
    <td class="titulo-checklist">
        <?= htmlspecialchars($c['titulo']) ?>
    </td>

    <td>
        <?= $c['concluido']
            ? '<span class="badge bg-success">Finalizado</span>'
            : '<span class="badge bg-warning text-dark">Aberto</span>' ?>
    </td>

    <td>
        <?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?>
    </td>

    <td class="d-flex gap-1">

        <?php if ($c['concluido'] && $c['hash_documento']): ?>
            <a href="/checklist/pdf/gerar_publico.php?hash=<?= $c['hash_documento'] ?>"
               target="_blank"
               class="btn btn-sm btn-outline-primary">
               📄 PDF
            </a>
        <?php endif; ?>

        <?php if (!$c['modelo_publico']): ?>
            <button
                class="btn btn-sm btn-outline-danger"
                onclick="excluirChecklist(<?= $c['id'] ?>)">
                🗑 Excluir
            </button>
        <?php endif; ?>

    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</main>
</div>
</div>

<?php require __DIR__ . '/../../include/footer.php'; ?>

<script>
/* 🔍 FILTRO EM TEMPO REAL NA TABELA */
const input  = document.getElementById('buscaChecklist');
const linhas = document.querySelectorAll('#tabelaChecklists tbody tr');

input.addEventListener('input', () => {
    const termo = input.value.toLowerCase().trim();

    linhas.forEach(tr => {
        const titulo = tr.querySelector('.titulo-checklist').innerText.toLowerCase();
        tr.style.display = titulo.includes(termo) ? '' : 'none';
    });
});

/* 🗑 EXCLUSÃO */
function excluirChecklist(id) {
    if (!confirm('Deseja excluir este checklist? Esta ação é irreversível.')) return;

    fetch('/checklist/historico/excluir.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.ok) location.reload();
        else alert(resp.erro);
    });
}
</script>

</body>
</html>
