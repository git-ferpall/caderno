document.addEventListener('change', function(e){
    if (!e.target.classList.contains('item-tipo')) return;

    const card = e.target.closest('.item-card');
    if (!card) return;

    const body = card.querySelector('.item-body');
    const tipo = e.target.value;
    const key  = card.dataset.key;

    body.innerHTML = renderCampos(tipo, key);
});

function renderCampos(tipo, key){
    switch(tipo){
        case 'texto_curto':
            return `
                <label>Limite de caracteres</label>
                <input type="number"
                       name="item_max_caracteres[${key}]"
                       value="120">
            `;
        case 'unica':
            return `
                <label>Opções</label>
                <textarea name="item_opcoes[${key}]"></textarea>
            `;
        case 'multipla':
            return `
                <label>Opções</label>
                <textarea name="item_opcoes[${key}]"></textarea>

                <label>Mínimo</label>
                <input type="number" name="item_min[${key}]" value="2">

                <label>Máximo</label>
                <input type="number" name="item_max[${key}]" value="3">
            `;
        case 'nota_estrela':
            return `<div>⭐⭐⭐⭐⭐</div>`;
        case 'nota_0_10':
            return `<input type="range" min="0" max="10">`;
        default:
            return '';
    }
}
