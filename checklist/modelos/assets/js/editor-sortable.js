document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('itens');
    if (!container) return;

    new Sortable(container, {
        handle: '.handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        filter: 'input, textarea, label, button',
        preventOnFilter: false
    });
});
