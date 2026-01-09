<?php
require_once '../../config/db.php';
session_start();

$id = $_GET['id'];

$modelo = $pdo->prepare("SELECT * FROM checklist_modelos WHERE id = ?");
$modelo->execute([$id]);
$modelo = $modelo->fetch(PDO::FETCH_ASSOC);

$itens = $pdo->prepare("
    SELECT *
    FROM checklist_modelo_itens
    WHERE modelo_id = ?
    ORDER BY ordem ASC, id ASC
");
$itens->execute([$id]);
$itens = $itens->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Editar Modelo</h3>

<form method="post" action="salvar.php" class="mb-4">
    <input type="hidden" name="id" value="<?= $modelo['id'] ?>">

    <div class="mb-2">
        <label>Título</label>
        <input class="form-control" name="titulo" value="<?= htmlspecialchars($modelo['titulo']) ?>">
    </div>

    <div class="mb-3">
        <label>Descrição</label>
        <textarea class="form-control" name="descricao"><?= htmlspecialchars($modelo['descricao']) ?></textarea>
    </div>

    <button class="btn btn-primary">Salvar modelo</button>
</form>

<hr>

<h5>Perguntas</h5>

<form method="post" action="../itens/salvar.php" class="mb-3">
    <input type="hidden" name="modelo_id" value="<?= $modelo['id'] ?>">

    <div class="input-group">
        <input name="descricao" class="form-control" placeholder="Nova pergunta" required>
        <button class="btn btn-success">Adicionar</button>
    </div>
</form>

<ul class="list-group" id="lista-itens">
<?php foreach ($itens as $i): ?>
<li class="list-group-item d-flex align-items-center"
    data-id="<?= $i['id'] ?>">
    <span class="me-2 drag-handle" style="cursor:move;">☰</span>
    <span class="flex-grow-1"><?= htmlspecialchars($i['descricao']) ?></span>
    <a href="../itens/excluir.php?id=<?= $i['id'] ?>"
       class="btn btn-sm btn-danger ms-2">X</a>
</li>
<?php endforeach ?>
</ul>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
const lista = document.getElementById('lista-itens');

Sortable.create(lista, {
    animation: 150,
    handle: '.drag-handle',
    onEnd: () => {

        let ordem = [];

        lista.querySelectorAll('li').forEach((el, index) => {
            ordem.push({
                id: el.dataset.id,
                ordem: index + 1
            });
        });

        fetch('../itens/ordenar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(ordem)
        });
    }
});
</script>
