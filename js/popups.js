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

let selectedId = null; // vai guardar a seleção temporária

document.querySelectorAll('.select-propriedade').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        selectedId = id;

        // Resetar visual
        document.querySelectorAll('.item-propriedade').forEach(item => {
            item.classList.remove('ativo');
            const b = item.querySelector('button');
            if (b) {
                b.textContent = 'Selecionar';
                b.classList.remove('fundo-verde');
                b.classList.add('fundo-azul');
            }
        });

        // Ativar visualmente a escolhida
        const selected = document.getElementById('prop-' + id);
        if (selected) {
            selected.classList.add('ativo');
            const b = selected.querySelector('button');
            if (b) {
                b.textContent = 'Selecionada';
                b.classList.remove('fundo-azul');
                b.classList.add('fundo-verde');
            }
        }
    });
});

// Quando clicar em Voltar/Confirmar, aplica no banco
function confirmarPropriedade() {
    if (!selectedId) {
        closePopup();
        return;
    }

    fetch('/funcoes/ativar_propriedade.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(selectedId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            closePopup();
            location.reload(); // ou atualizar apenas o card da home
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(err => {
        alert('Erro de rede: ' + err);
    });
}
