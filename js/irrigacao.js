document.addEventListener('DOMContentLoaded', () => {

  // === DUPLICAR ÁREAS ===
  const addAreaBtn = document.querySelector('.add-area');
  if (addAreaBtn) {
    addAreaBtn.addEventListener('click', () => {
      const lista = document.getElementById('lista-areas');
      const box = lista.querySelector('.form-box-area').cloneNode(true);
      box.querySelector('select').value = '';
      lista.appendChild(box);
    });
  }

  // === DUPLICAR PRODUTOS ===
  const addProdutoBtn = document.querySelector('.add-produto');
  if (addProdutoBtn) {
    addProdutoBtn.addEventListener('click', () => {
      const lista = document.getElementById('lista-produtos');
      const box = lista.querySelector('.form-box-produto').cloneNode(true);
      box.querySelector('select').value = '';
      lista.appendChild(box);
    });
  }

  // === SUBMISSÃO DO FORMULÁRIO ===
  const form = document.getElementById('form-irrigacao');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const data = new FormData(form);

      try {
        const resp = await fetch('../funcoes/salvar_irrigacao.php', {
          method: 'POST',
          body: data
        });

        const json = await resp.json();

        if (json.ok) {
          alert('✅ Irrigação registrada com sucesso!');
          form.reset();
          // opcional: rola pro topo
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          alert('❌ Não foi possível salvar: ' + (json.msg || 'Erro desconhecido'));
        }

      } catch (err) {
        console.error(err);
        alert('⚠️ Falha na comunicação com o servidor.');
      }
    });
  }

});
