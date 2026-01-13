const overlay = document.getElementById('popup-overlay');
const popupCancel = document.getElementById('popup-cancel');
const popupSuccess = document.getElementById('popup-success');
const popupFailed = document.getElementById('popup-failed');
const popupProp = document.getElementById('popup-prop');

function closePopup() {
    if (overlay) overlay.classList.add('d-none');
    if (popupCancel) popupCancel.classList.add('d-none');
    if (popupSuccess) popupSuccess.classList.add('d-none');
    if (popupFailed) popupFailed.classList.add('d-none');
    if (popupProp) popupProp.classList.add('d-none');
}

/* Cancelar */
document.querySelectorAll('.form-cancel').forEach(el => {
    el.addEventListener('click', () => {
        if (overlay) overlay.classList.remove('d-none');
        if (popupCancel) popupCancel.classList.remove('d-none');
    });
});

/* Salvar */
document.querySelectorAll('.form-save').forEach(el => {
    el.addEventListener('click', event => {

        const form = event.target.closest('.main-form');
        if (!form) return;

        const req = form.querySelectorAll('[required]');
        let allFilled = true;

        req.forEach(field => {
            if (!field.value.trim()) allFilled = false;
        });

        if (allFilled) {

            if (overlay) overlay.classList.remove('d-none');
            if (popupSuccess) popupSuccess.classList.remove('d-none');

            const timeout = setTimeout(() => form.submit(), 3000);

            const btnOk = document.getElementById('btn-ok');
            if (btnOk) {
                btnOk.addEventListener('click', () => {
                    clearTimeout(timeout);
                    form.submit();
                }, { once: true });
            }

        } else {
            if (overlay) overlay.classList.remove('d-none');
            if (popupFailed) popupFailed.classList.remove('d-none');
        }
    });
});

function altProp() {
    if (overlay) overlay.classList.remove('d-none');
    if (popupProp) popupProp.classList.remove('d-none');
}

let selectedPropId = null;

/* Seleção de propriedade */
document.querySelectorAll('.select-propriedade').forEach(btn => {
    btn.addEventListener('click', function () {

        document.querySelectorAll('.select-propriedade').forEach(b => {
            b.classList.remove('selecionada');
            b.classList.add('fundo-azul');
            b.textContent = 'Selecionar';
            b.disabled = false;
        });

        this.classList.remove('fundo-azul');
        this.classList.add('selecionada');
        this.textContent = 'Selecionada';
        this.disabled = true;

        const item = this.closest('.item-propriedade');
        if (item) selectedPropId = item.dataset.id;
    });
});

/* Ativar */
const btnAtivar = document.getElementById('btn-ativar');

if (btnAtivar) {
    btnAtivar.addEventListener('click', () => {

        if (!selectedPropId) {
            if (overlay) overlay.classList.remove('d-none');
            if (popupFailed) {
                popupFailed.classList.remove('d-none');
                const txt = popupFailed.querySelector('.popup-text');
                if (txt) txt.textContent = "Selecione uma propriedade antes de ativar!";
            }
            return;
        }

        fetch('/funcoes/ativar_propriedade.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(selectedPropId)
        })
        .then(r => r.json())
        .then(data => {

            if (data.ok) {

                closePopup();
                if (overlay) overlay.classList.remove('d-none');

                const popup = document.getElementById('popup-ativar');
                if (popup) {
                    popup.classList.remove('d-none');
                    const title = popup.querySelector('.popup-title');
                    if (title) title.textContent = "Propriedade ativada com sucesso!";

                    const btnOk = popup.querySelector('#btn-ok');
                    if (btnOk) {
                        btnOk.onclick = () => {
                            closePopup();
                            location.reload();
                        };
                    }
                }

            } else {
                if (overlay) overlay.classList.remove('d-none');
                if (popupFailed) {
                    popupFailed.classList.remove('d-none');
                    const txt = popupFailed.querySelector('.popup-text');
                    if (txt) txt.textContent = data.error || "Erro ao ativar propriedade.";
                }
            }
        })
        .catch(err => {
            if (overlay) overlay.classList.remove('d-none');
            if (popupFailed) {
                popupFailed.classList.remove('d-none');
                const txt = popupFailed.querySelector('.popup-text');
                if (txt) txt.textContent = "Falha na requisição: " + err;
            }
        });
    });
}
