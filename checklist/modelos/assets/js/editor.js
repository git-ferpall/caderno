/**
 * Checklist Editor
 * - Perguntas
 * - Sessões
 * - Drag & Drop
 * - Associação pergunta → sessão
 */

document.addEventListener('DOMContentLoaded', () => {
    initSortable();
});

/* ======================================================
   SORTABLE
====================================================== */

function initSortable() {
    const container = document.getElementById('itens');
    if (!container) return;

    new Sortable(container, {
        animation: 180,
        handle: '.handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        fallbackOnBody: true,
        swapThreshold: 0.65
    });
}

/* ======================================================
   ADICIONAR PERGUNTA
====================================================== */

function addPergunta() {
    const key = 'new_' + Date.now();

    const div = document.createElement('div');
    div.className = 'item-card';
    div.dataset.key = key;

    div.innerHTML = `
        <span class="handle">☰</span>

        <input type="hidden" name="item_key[]" value="${key}">

        <div class="item-header">
            <input type="text"
                   class="item-title"
                   name="item_desc[${key}]"
                   placeholder="Digite a pergunta"
                   required>

            <select name="item_tipo[${key}]"
                    class="item-tipo"
                    onchange="renderBody(this)">
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

/* ======================================================
   ADICIONAR SESSÃO
====================================================== */

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
                onclick="this.closest('.sessao-card').remove()">🗑</button>
    `;

    document.getElementById('itens').appendChild(div);
}

/* ======================================================
   RENDERIZA CAMPOS DINÂMICOS
====================================================== */

function renderBody(select) {
    const card = select.closest('.item-card');
    const body = card.querySelector('.item-body');
    const tipo = select.value;
    const key = card.dataset.key;

    body.innerHTML = '';

    if (tipo === 'texto_curto') {
        body.innerHTML = `
            <label>Limite de caracteres</label>
            <input type="number"
                   name="item_max[${key}]"
                   min="10"
                   max="255"
                   value="100">
        `;
    }

    if (tipo === 'texto_longo') {
        body.innerHTML = `
            <label>Resposta longa</label>
            <textarea disabled placeholder="Resposta do usuário"></textarea>
        `;
    }

    if (tipo === 'unica' || tipo === 'multipla') {
        body.innerHTML = `
            <label>Opções (uma por linha)</label>
            <textarea name="item_opcoes[${key}]"
                      placeholder="Ex: Sim&#10;Não"></textarea>

            <label>Mínimo</label>
            <input type="number"
                   name="item_min[${key}]"
                   min="1"
                   value="${tipo === 'unica' ? 1 : 2}">

            <label>Máximo</label>
            <input type="number"
                   name="item_max[${key}]"
                   min="1"
                   value="${tipo === 'unica' ? 1 : 2}">
        `;
    }

    if (tipo === 'nota_estrela') {
        body.innerHTML = `
            <label>Pré-visualização</label>
            <div class="preview-stars">★★★★★</div>
        `;
    }

    if (tipo === 'nota_0_10') {
        body.innerHTML = `
            <label>Intervalo</label>
            <div>0 até 10</div>
        `;
    }
}

/* ======================================================
   ASSOCIAR PERGUNTAS À SESSÃO (ANTES DE SALVAR)
====================================================== */

document.addEventListener('submit', e => {
    if (!e.target.matches('form')) return;

    let sessaoAtual = null;

    document.querySelectorAll('#itens > *').forEach(el => {

        if (el.classList.contains('sessao-card')) {
            sessaoAtual = el.dataset.key;
            return;
        }

        if (el.classList.contains('item-card')) {
            const key = el.dataset.key;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `item_sessao[${key}]`;
            input.value = sessaoAtual || '';

            el.appendChild(input);
        }
    });
});
