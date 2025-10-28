document.addEventListener('DOMContentLoaded', () => {

  // Detecta todos os botões "Salvar" dos formulários de fertilizante da hidroponia
  document.querySelectorAll('.form-fertilizante .form-save').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      const form = btn.closest('.form-fertilizante');
      const formId = form.id;

      // Extrai IDs do form (ex: add-e-3-b-01-fertilizante)
      const match = formId.match(/add-e-(\d+)-b-(.+)-fertilizante/);
      if (!match) {
        alert('Erro interno ao identificar a estufa ou bancada.');
        return;
      }

      const estufaId = match[1];
      const bancadaNome = match[2];

      // Captura os valores do formulário
      const produtoSelect = form.querySelector('select[id*="-produto"]');
      const produtoNome = produtoSelect ? produtoSelect.options[produtoSelect.selectedIndex].text.trim() : '';
      const produtoVal = produtoSelect ? produtoSelect.value : '';

      const dose = form.querySelector('input[id*="-dose"]').value.trim();
      const tipo = form.querySelector('input[name*="-tipo"]:checked').value;
      const obs = form.querySelector('textarea[id*="-obs"]').value.trim();

      // Verifica se o produto é válido
      if (!produtoVal || produtoVal === '-') {
        alert('Selecione o produto aplicado.');
        return;
      }

      // Monta o nome descritivo para a solicitação (mantendo padrão do seu PHP)
      const nomeCompleto = `${produtoNome} — Estufa ${estufaId}, Bancada ${bancadaNome}`;

      // Envia para o backend (função de solicitação existente)
      try {
        const resp = await fetch('../funcoes/salvar_fertilizante.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            nome: nomeCompleto,
            obs: obs
          })
        });

        const data = await resp.json();

        if (data.ok) {
          alert('✅ ' + data.msg);
          form.classList.add('d-none'); // esconde o formulário
        } else {
          alert('❌ ' + (data.msg || 'Erro ao salvar solicitação.'));
        }
      } catch (err) {
        console.error(err);
        alert('Erro de comunicação com o servidor.');
      }
    });
  });

  // Botão "Cancelar"
  document.querySelectorAll('.form-fertilizante .form-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('.form-fertilizante');
      form.classList.add('d-none');
    });
  });

});
