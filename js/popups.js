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

// Seleção única com troca de botão
document.querySelectorAll('.select-propriedade').forEach(btn => {
    btn.addEventListener('click', function() {
        // resetar todos os botões
        document.querySelectorAll('.select-propriedade').forEach(b => {
            b.classList.remove('selecionada');
            b.classList.add('fundo-azul');
            b.textContent = 'Selecionar';
            b.disabled = false;
        });

        // aplicar no botão clicado
        this.classList.remove('fundo-azul');
        this.classList.add('selecionada');
        this.textContent = 'Selecionada';
        this.disabled = true; // opcional

        // armazenar ID da propriedade
        const item = this.closest('.item-propriedade');
        selectedPropId = item.dataset.id;
    });
});

document.getElementById('btn-ativar').addEventListener('click', function() {
    if (!selectedPropId) {
        showPopupFailed("Erro", "Selecione uma propriedade antes de ativar!");
        return;
    }

    fetch('/funcoes/ativar_propriedade.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(selectedPropId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            showPopupSuccess("Propriedade ativada com sucesso!");
            
            // substitui ação do botão Ok
            const okBtn = document.querySelector('#popup-success #btn-ok');
            okBtn.onclick = () => {
                closePopup();
                location.reload();
            };
        } else {
            showPopupFailed("Erro ao ativar", data.error || "Falha desconhecida.");
        }
    })
    .catch(err => {
        showPopupFailed("Erro inesperado", "Falha na requisição: " + err);
    });
});
