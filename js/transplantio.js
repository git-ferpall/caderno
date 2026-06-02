document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-transplantio");

  function preencherAreas(data) {
    if (!Array.isArray(data)) return;

    document.querySelectorAll(".area-origem-select").forEach((sel) => {
      const valorAtual = sel.value;
      sel.innerHTML = '<option value="">Selecione a área de origem</option>';
      data.forEach((item) => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = `${item.nome} (${item.tipo})`;
        if (item.id == valorAtual) opt.selected = true;
        sel.appendChild(opt);
      });
    });

    document.querySelectorAll(".area-destino-select").forEach((sel) => {
      const valorAtual = sel.value;
      sel.innerHTML = '<option value="">Selecione a área de destino</option>';
      data.forEach((item) => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = `${item.nome} (${item.tipo})`;
        if (item.id == valorAtual) opt.selected = true;
        sel.appendChild(opt);
      });
    });
  }

  function carregarAreas() {
    fetch("/funcoes/buscar_areas.php", { credentials: "same-origin" })
      .then((r) => r.json())
      .then((data) => {
        if (Array.isArray(data)) {
          preencherAreas(data);
          return;
        }
        if (typeof OfflineSync !== "undefined") {
          OfflineSync.refillCatalogSelects();
        }
      })
      .catch((err) => {
        console.error("Erro ao carregar áreas:", err);
        if (typeof OfflineSync !== "undefined") {
          OfflineSync.refillCatalogSelects();
        }
      });
  }

  function carregarProdutos() {
    fetch("/funcoes/buscar_produtos.php", { credentials: "same-origin" })
      .then((r) => r.json())
      .then((data) => {
        const sel = document.getElementById("produto");
        if (!sel) return;
        if (!Array.isArray(data)) {
          if (typeof OfflineSync !== "undefined") OfflineSync.refillCatalogSelects();
          return;
        }
        sel.innerHTML = '<option value="">Selecione o produto</option>';
        data.forEach((item) => {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.nome;
          sel.appendChild(opt);
        });
      })
      .catch((err) => {
        console.error("Erro ao carregar produtos:", err);
        if (typeof OfflineSync !== "undefined") OfflineSync.refillCatalogSelects();
      });
  }

  carregarAreas();
  carregarProdutos();

  if (!navigator.onLine && typeof OfflineSync !== "undefined") {
    setTimeout(() => OfflineSync.refillCatalogSelects(), 500);
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      form.querySelectorAll('button[type="submit"]').forEach((el) => el.blur());

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const formData = new FormData(form);

      try {
        const resp = await fetch("/funcoes/salvar_transplantio.php", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });
        const data = await resp.json();

        if (data.ok) {
          const msg =
            data.msg ||
            (data.offline
              ? "Salvo no dispositivo. Sincroniza quando houver internet."
              : "Transplantio salvo com sucesso!");
          showPopup("success", msg);
          setTimeout(() => {
            window.location.href = "/home/apontamento";
          }, 1200);
        } else {
          showPopup("failed", data.err || data.msg || "Erro ao salvar o transplantio.");
        }
      } catch (err) {
        showPopup("failed", "Falha ao salvar: " + (err.message || err));
      }
    });
  }
});

function showPopup(tipo, mensagem) {
  const overlay = document.getElementById("popup-overlay");
  const popupSuccess = document.getElementById("popup-success");
  const popupFailed = document.getElementById("popup-failed");

  document.querySelectorAll(".popup-box").forEach((p) => p.classList.add("d-none"));
  if (overlay) overlay.classList.remove("d-none");

  if (tipo === "success") {
    if (popupSuccess) {
      popupSuccess.classList.remove("d-none");
      const title = popupSuccess.querySelector(".popup-title");
      if (title) title.textContent = mensagem;
    } else {
      alert(mensagem);
    }
  } else {
    if (popupFailed) {
      popupFailed.classList.remove("d-none");
      const text = popupFailed.querySelector(".popup-text");
      if (text) text.textContent = mensagem;
    } else {
      alert(mensagem);
    }
  }

  setTimeout(() => {
    overlay?.classList.add("d-none");
    popupSuccess?.classList.add("d-none");
    popupFailed?.classList.add("d-none");
  }, 4000);
}
