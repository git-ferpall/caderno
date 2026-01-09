<form method="post" action="salvar.php">

<input type="hidden" name="modelo_id" value="<?= $modelo_id ?>">
<?php if (!empty($item['id'])): ?>
<input type="hidden" name="id" value="<?= $item['id'] ?>">
<?php endif; ?>

<div class="mb-3">
    <label class="form-label">Descrição do item</label>
    <input type="text"
           name="descricao"
           class="form-control"
           required
           value="<?= htmlspecialchars($item['descricao'] ?? '') ?>">
</div>

<div class="form-check">
    <input class="form-check-input"
           type="checkbox"
           name="permite_observacao"
           value="1"
           <?= !empty($item['permite_observacao']) ? 'checked' : '' ?>>
    <label class="form-check-label">
        Permitir observação
    </label>
</div>

<div class="form-check">
    <input class="form-check-input"
           type="checkbox"
           name="permite_foto"
           value="1"
           <?= !empty($item['permite_foto']) ? 'checked' : '' ?>>
    <label class="form-check-label">
        Permitir foto
    </label>
</div>

<div class="form-check mb-3">
    <input class="form-check-input"
           type="checkbox"
           name="permite_anexo"
           value="1"
           <?= !empty($item['permite_anexo']) ? 'checked' : '' ?>>
    <label class="form-check-label">
        Permitir documento
    </label>
</div>

<button type="submit" class="btn btn-primary">
    💾 Salvar item
</button>

</form>
