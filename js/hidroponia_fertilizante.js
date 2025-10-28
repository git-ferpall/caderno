document.addEventListener("DOMContentLoaded", () => {

  // === Carregar fertilizantes em TODOS os selects ===
  async function carregarFertilizantes() {
    try {
      const resp = await fetch("../funcoes/buscar_fertilizantes.php");
      const data = await resp.json();

      // Para cada select dentro dos formulários de fertilizante
      document.querySelectorAll('.form-fertilizante select[id*="-produto"]').forEach(sel => {
        sel.innerHTML = '<option value="">Selecione o fertilizante</option>';

        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });

        // Adiciona opção "Outro"
        const outro = document.createElement("option");
        outro.value = "outro";
        outro.textContent = "Outro (digitar manualmente)";
        sel.appendChild(outro);
      });
    } catch (err) {
      console.error("Erro ao carregar fertilizantes:", err);
    }
  }

  carregarFertilizantes();

  // === Exibir campo texto quando selecionar "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-fertilizante select[id*="-produto"]')) {
      const sel = e.target;
      const form = sel.closest(".form-fertilizante");

      // Procura o input associado (se não existir, cria dinamicamente)
      let inputOutro = form.querySelector(".fertilizante-outro");
      if (!inputOutro) {
        inputOutro = document.createElement("input");
        inputOutro.type = "text";
        inputOutro.className = "form-text fertilizante-outro";
        inputOutro.placeholder = "Digite o nome do fertilizante";
        inputOutro.style.marginTop = "8px";
        inputOutro.style.display = "none";

        // Insere o input logo abaixo do select
        sel.insertAdjacentElement("afterend", inputOutro);
      }

      // Mostra ou oculta o campo dependendo da seleção
      if (sel.value === "outro") {
        inputOutro.style.display = "block";
      } else {
        inputOutro.style.display = "none";
      }
    }
  });

  // === Botão "Salvar" ===
  document.querySelectorAll('.form-fertilizante .form-save').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      const form = btn.closest('.form-fertilizante');
      const formId = form.id;
      const match = formId.match(/add-e-(\d+)-b-(.+)-fertilizante/);
      if (!match) return alert("Erro interno ao identificar estufa/bancada");

      const estufaId = match[1];
      const bancadaNome = match[2];

      const produtoSel = form.querySelector('select[id*="-produto"]');
      const produtoVal = produtoSel.value;
      const produtoNome = produtoSel.options[produtoSel.selectedIndex].text.trim();
      const outroInput = form.querySelector('.fertilizante-outro');
      const produtoFinal = (produtoVal === "outro" && outroInput)
        ? outroInput.value.trim()
        : produtoNome;

      const dose = form.querySelector('input[id*="-dose"]').value.trim();
      const tipo = form.querySelector('input[name*="-tipo"]:checked').value;
      const obs = form.querySelector('textarea[id*="-obs"]').value.trim();

      if (!produtoFinal) {
        alert('Informe o nome do fertilizante.');
        return;
      }

      try {
        const resp = await fetch("../funcoes/salvar_fertilizante.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            nome: produtoFinal,
            obs: `Estufa ${estufaId}, Bancada ${bancadaNome} — ${obs}`
          })
        });
        const data = await resp.json();

        if (data.ok) {
          alert("✅ " + data.msg);
          form.classList.add("d-none");
        } else {
          alert("❌ " + (data.msg || "Erro ao salvar fertilizante."));
        }
      } catch (err) {
        console.error(err);
        alert("Erro de comunicação com o servidor.");
      }
    });
  });

  // === Botão "Cancelar" ===
  document.querySelectorAll('.form-fertilizante .form-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('.form-fertilizante');
      form.classList.add('d-none');
    });
  });

});
