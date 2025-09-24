const overlay = document.getElementById('popup-overlay');
const popupCancel = document.getElementById('popup-cancel');
const popupSuccess = document.getElementById('popup-success');
const popupFailed = document.getElementById('popup-failed');
const popupProp = document.getElementById('popup-prop');

function closePopup() {
    overlay.classList.add('d-none');
    popupCancel.classList.add('d-none');
    popupSuccess.classList.add('d-none');
    popupFailed.classList.add('d-none');
    popupProp.classList.add('d-none');
}

document.querySelectorAll('.form-cancel').forEach(function(el) {
    el.addEventListener('click', function(event) {
        overlay.classList.remove('d-none');
        popupCancel.classList.remove('d-none');
    });
});

document.querySelectorAll('.form-save').forEach(function(el) {
    el.addEventListener('click', function(event) {
        const form = event.target.closest('.main-form');

        // Verifica se todos os campos required estão preenchidos
        const req = form.querySelectorAll('[required]');
        let allFilled = true;

        req.forEach(field => {
            if (!field.value.trim()) {
                allFilled = false;
            } 
        });

        if (allFilled) {
            overlay.classList.remove('d-none');
            popupSuccess.classList.remove('d-none');

            const timeout = setTimeout(() => {
                form.submit();
            }, 3000);

            // Se o usuário clicar em btn-ok, cancela o timeout e submete imediatamente
            document.getElementById('btn-ok').addEventListener('click', function () {
                clearTimeout(timeout);
                form.submit();
            }, { once: true }); // Garante que o listener será executado só uma vez
        } else {
            overlay.classList.remove('d-none');
            popupFailed.classList.remove('d-none');
        }
    });
});

function altProp() {
    overlay.classList.remove('d-none');
    popupProp.classList.remove('d-none');
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('select-propriedade')) {
        const id = e.target.getAttribute('data-id');

        fetch('/funcoes/ativar_propriedade.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // 1. Atualiza a propriedade no "home"
                const tituloHome = document.querySelector('#prop-ativa-nome');
                if (tituloHome) {
                    tituloHome.textContent = data.nome; // backend retorna o nome
                }

                // 2. Atualiza os botões do popup
                document.querySelectorAll('.item-propriedade').forEach(div => {
                    div.querySelector('.item-edit').innerHTML = `
                        <button class="select-propriedade" data-id="${div.dataset.id}">
                            Selecionar
                        </button>
                    `;
                    div.classList.remove('fundo-preto');
                });

                const ativoDiv = document.querySelector(`#prop-${id}`);
                if (ativoDiv) {
                    ativoDiv.classList.add('fundo-preto');
                    ativoDiv.querySelector('.item-edit').innerHTML = `
                        <span class="badge fundo-verde">Ativa</span>
                    `;
                }

                // Fecha popup
                closePopup();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(err => {
            alert('Erro de rede: ' + err);
        });
    }
});
