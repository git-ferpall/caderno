<form method="post" action="salvar.php">
    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="titulo" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control"></textarea>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="publico" value="1">
        <label class="form-check-label">
            Modelo padrão do sistema
        </label>
    </div>

    <button class="btn btn-success">Salvar</button>
    <a href="index.php" class="btn btn-secondary">Cancelar</a>
</form>
