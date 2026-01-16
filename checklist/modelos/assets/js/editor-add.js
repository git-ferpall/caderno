function addPergunta() {
    const key = 'new_' + Date.now();

    const div = document.createElement('div');
    div.className = 'item-card';
    div.dataset.key = key;
    div.dataset.tipo = 'texto_longo';

    div.innerHTML = `
        <input type="hidden" name="item_key[]" value="${key}">

        <div class="item-header">
            <span class="handle">☰</span>

            <input type="text"
                   class="item-title"
                   name="item_desc[${key}]"
                   placeholder="Digite a pergunta"
                   required>

            <select name="item_tipo[${key}]" class="item-tipo">
                <option value="texto_longo">Texto longo</option>
                <option value="texto_curto">Texto curto</option>
                <option value="data">Data</option>
                <option value="unica">Única escolha</option>
                <option value="multipla">Múltipla escolha</option>
                <option value="nota_estrela">Nota ⭐</option>
                <option value="nota_0_10">Nota 0–10</option>
            </select>

            <button type="button"
                    class="btn-remover-text"
                    onclick="this.closest('.item-card').remove()">🗑</button>
        </div>

        <div class="item-body"></div>
    `;

    document.getElementById('itens').appendChild(div);
}

function addSessao() {
    const key = 'sessao_' + Date.now();

    const div = document.createElement('div');
    div.className = 'sessao-card';
    div.dataset.key = key;

    div.innerHTML = `
        <span class="handle">☰</span>

        <input type="hidden" name="item_key[]" value="${key}">
        <input type="hidden" name="item_tipo[${key}]" value="sessao">

        <input type="text"
               name="item_desc[${key}]"
               placeholder="Nome da sessão">

        <button type="button"
                class="btn-remover-text"
                onclick="this.closest('.sessao-card').remove()">
            🗑
        </button>
    `;

    document.getElementById('itens').appendChild(div);
}

