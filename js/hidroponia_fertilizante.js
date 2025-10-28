document.addEventListener("DOMContentLoaded", () => {
  // === Função para carregar todos os fertilizantes ===
  async function carregarFertilizantes() {
    try {
      const resp = await fetch("../funcoes/buscar_fertilizantes.php");
      const data = await resp.json();

      // Preenche todos os selects de fertilizante
      document.querySelectorAll('.form-fertilizante select[id*="-produto"]').forEach(sel => {
        sel.innerHTML = '<option value="-">Selecione o fertilizante</option>';

        data.forEach(item => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });

        // Adiciona a opção "Outro"
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

  // === Exibir campo extra ao escolher "Outro" ===
  document.addEventListener("change", (e) => {
    if (e.target.matches('.form-fertilizante select[id*="-produto"]')) {
      const sel = e.target;
      const form = sel.closest(".form-fertilizante");

      // Procura ou cria o campo de texto extra
      let inputOutro = form.querySelector(".fertilizante-outro");
      if (!inputOutro) {
        inputOutro = document.createElement("input");
        inputOutro.type = "text";
        inputOutro.className = "form-text fertilizante-outro";
        inputOutro.placeholder = "Digite o nome do fertilizante";
        inputOutro.style.marginTop = "8px";
        inputOutro.style.display = "none";
        sel.insertAdjacentElement("afterend", inputOutro);
      }

      // Mostra/oculta o campo conforme a seleção
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

      // Extrai IDs de estufa e bancada
      const match = formId.match(/add-e-(\d+)-b-(.+)-fertilizante/);
      if (!match) {
        alert("Erro ao identificar estufa/bancada.");
        return;
      }

      const estufaId = match[1];
      const bancadaNome = match[2];

      // Captura os valores
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

      // Validações básicas
      if (!produtoFinal) {
        alert("Selecione ou digite o nome do fertilizante.");
        return;
      }

      try {
        // Envia os dados ao PHP
        const resp = await fetch("../funcoes/salvar_fertilizante_hidroponia.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            estufa_id: estufaId,
            bancada_nome: bancadaNome,
            nome: produtoFinal,
            dose: dose,
            tipo: tipo,
            obs: obs
          })
        });

        const data = await resp.json();
        console.log(data); // útil para debug

        if (data.ok) {
          alert(data.msg || "✅ Fertilizante aplicado com sucesso!");
          form.classList.add("d-none");
        } else {
          alert("❌ " + (data.err || data.msg || "Erro ao salvar fertilizante."));
        }
      } catch (err) {
        console.error("Erro na comunicação:", err);
        alert("❌ Falha na comunicação com o servidor.");
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
