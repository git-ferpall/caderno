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
let selectedPropId = null;

// Seleção única
document.querySelectorAll('.select-propriedade').forEach(btn => {
    btn.addEventListener('click', function() {
        // limpar seleções anteriores
        document.querySelectorAll('.item-propriedade').forEach(el => {
            el.classList.remove('selecionada');
        });

        // marcar selecionada
        const item = this.closest('.item-propriedade');
        item.classList.add('selecionada');
        selectedPropId = item.dataset.id;
    });
});

// Enviar para o backend ao clicar em "Ativar"
document.getElementById('btn-ativar').addEventListener('click', function() {
    if (!selectedPropId) {
        alert('Selecione uma propriedade antes de ativar!');
        return;
    }

    fetch('/api/ativar_propriedade.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(selectedPropId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            alert('Propriedade ativada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(err => alert('Falha na requisição: ' + err));
});

