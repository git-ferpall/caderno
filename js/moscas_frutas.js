document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("form-moscas");
  const qtdMoscas = document.getElementById("qtd_moscas");

  // === Aviso e ícone de status conforme quantidade ===
  if (qtdMoscas) {
    // Cria o container para aviso (abaixo do campo)
    const avisoContainer = document.createElement("div");
    avisoContainer.style.display = "flex";
    avisoContainer.style.alignItems = "center";
    avisoContainer.style.gap = "6px";
    avisoContainer.style.marginTop = "6px";
    avisoContainer.style.fontSize = "0.9em";

    const icon = document.createElement("span");
    const avisoQtd = document.createElement("span");

    avisoContainer.appendChild(icon);
    avisoContainer.appendChild(avisoQtd);

    // adiciona abaixo do campo
    qtdMoscas.parentElement.appendChild(avisoContainer);

    const atualizarAviso = () => {
      const valor = qtdMoscas.value.trim();

      if (valor === "" || parseFloat(valor) === 0) {
        icon.textContent = "⚠️";
        avisoQtd.textContent = "Para deixar o apontamento PENDENTE, mantenha este campo vazio ou zero.";
        avisoQtd.style.color = "orange";
      } else {
        icon.textContent = "✔️";
        avisoQtd.textContent = "Com quantidade informada, o status será CONCLUÍDO.";
        avisoQtd.style.color = "green";
      }
    };

    atualizarAviso();
    qtdMoscas.addEventListener("input", atualizarAviso);
  }

  // === Carregar ÁREAS ===
  function carregarAreas() {
    fetch("../funcoes/buscar_areas.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".area-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione a área</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = `${item.nome} (${item.tipo})`;
            sel.appendChild(opt);
          });
        });
      });
  }

  // === Carregar PRODUTOS ===
  function carregarProdutos() {
    fetch("../funcoes/buscar_produtos.php")
      .then(r => r.json())
      .then(data => {
        document.querySelectorAll(".produto-select").forEach(sel => {
          sel.innerHTML = '<option value="">Selecione o produto</option>';
          data.forEach(item => {
            const opt = document.createElement("option");
            opt.value = item.id;
            opt.textContent = item.nome;
            sel.appendChild(opt);
          });
        });
      });
  }

  carregarAreas();
  carregarProdutos();

  // === Botão adicionar área ===
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "area[]";
    novo.classList.add("area-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area";
    wrapper.appendChild(novo);
    lista.appendChild(wrapper);
    carregarAreas();
  });

  // === Botão adicionar produto ===
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    novo.removeAttribute("id");
    novo.name = "produto[]";
    novo.classList.add("produto-select");

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-produto";
    wrapper.appendChild(novo);
    lista.appendChild(wrapper);
    carregarProdutos();
  });

  // === Envio do formulário ===
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(form);

      try {
        const resp = await fetch("../funcoes/salvar_moscas_frutas.php", {
          method: "POST",
          body: formData
        });
        const data = await resp.json();

        if (data.ok) {
          showPopup("sucesso", data.msg);
          form.reset();
          carregarAreas();
          carregarProdutos();
        } else {
          showPopup("erro", data.msg);
        }
      } catch (err) {
        showPopup("erro", "Erro inesperado ao salvar apontamento.");
      }
    });
  }
});

// === Função de popup padrão ===
function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  let popup = (tipo === "sucesso") ? popupSuccess : popupFailed;

  if (overlay && popup) {
    overlay.classList.remove("d-none");
    popup.classList.remove("d-none");

    const msgBox = popup.querySelector(".popup-text") || popup.querySelector(".popup-title");
    if (msgBox) msgBox.textContent = mensagem;

    const btnOk = popup.querySelector(".popup-btn");
    if (btnOk) {
      btnOk.onclick = () => {
        overlay.classList.add("d-none");
        popup.classList.add("d-none");
      };
    }
  } else {
    alert(mensagem);
  }
}
