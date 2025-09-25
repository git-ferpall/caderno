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

// Função para registrar os eventos de seleção
function bindSelectPropriedade() {
    document.querySelectorAll('.select-propriedade').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');

            fetch('/funcoes/ativar_propriedade.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // 🔄 Resetar todos os botões para "Selecionar"
                    document.querySelectorAll('.item-propriedade').forEach(item => {
                        item.classList.remove('ativo');
                        const btn = item.querySelector('button');
                        if (btn) {
                            btn.textContent = 'Selecionar';
                            btn.disabled = false;
                            btn.classList.remove('fundo-verde');
                            btn.classList.add('fundo-azul');
                            btn.classList.add('select-propriedade'); // garante que continua clicável
                        }
                    });

                    // ✅ Ativar só o selecionado
                    const selected = document.getElementById('prop-' + id);
                    if (selected) {
                        selected.classList.add('ativo');
                        const btn = selected.querySelector('button');
                        if (btn) {
                            btn.textContent = 'Ativa';
                            btn.disabled = false; // deixa clicável para voltar depois
                            btn.classList.remove('fundo-azul');
                            btn.classList.add('fundo-verde');
                        }
                    }

                    // Fechar popup depois de atualizar
                    setTimeout(() => closePopup(), 1000);
                } else {
                    alert('Erro: ' + data.error);
                }
            })
            .catch(err => {
                alert('Erro de rede: ' + err);
            });
        });
    });
}

// 🔄 Garante que ao abrir a página ou reabrir o popup, os botões estão com eventos
bindSelectPropriedade();