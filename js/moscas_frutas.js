document.addEventListener("DOMContentLoaded", () => {

  // === Carregar áreas ===
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

  // === Carregar produtos ===
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

  // Adicionar área
  document.querySelector(".add-area").addEventListener("click", () => {
    const lista = document.getElementById("lista-areas");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    const wrap = document.createElement("div");
    wrap.className = "form-box form-box-area";
    wrap.appendChild(novo);
    lista.appendChild(wrap);
    carregarAreas();
  });

  // Adicionar produto
  document.querySelector(".add-produto").addEventListener("click", () => {
    const lista = document.getElementById("lista-produtos");
    const original = lista.querySelector("select");
    const novo = original.cloneNode(true);
    novo.value = "";
    const wrap = document.createElement("div");
    wrap.className = "form-box form-box-produto";
    wrap.appendChild(novo);
    lista.appendChild(wrap);
    carregarProdutos();
  });

  // === Envio do formulário ===
  const form = document.getElementById("form-moscas");
  form.addEventListener("submit", e => {
    e.preventDefault();
    const dados = new FormData(form);

    fetch("../funcoes/salvar_moscas_frutas.php", {
      method: "POST",
      body: dados
    })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          showPopup("success", res.msg || "Registro salvo com sucesso!");
          form.reset();
        } else {
          showPopup("failed", res.msg || "Erro ao salvar registro.");
        }
      })
      .catch(err => {
        showPopup("failed", "Falha na comunicação: " + err);
      });
  });
});

// === Popups padrão ===
function showPopup(tipo, msg) {
  const overlay = document.getElementById("popup-overlay");
  const success = document.getElementById("popup-success");
  const failed = document.getElementById("popup-failed");
  document.querySelectorAll(".popup-box").forEach(p => p.classList.add("d-none"));
  overlay.classList.remove("d-none");

  if (tipo === "success") {
    success.classList.remove("d-none");
    success.querySelector(".popup-title").textContent = msg;
  } else {
    failed.classList.remove("d-none");
    failed.querySelector(".popup-text").textContent = msg;
  }

  setTimeout(() => {
    overlay.classList.add("d-none");
    success.classList.add("d-none");
    failed.classList.add("d-none");
  }, 4000);
}
