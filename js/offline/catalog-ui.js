/**
 * UI de formulários offline: validação, botões +/salvar, transplantio (origem/destino).
 */
const OfflineCatalogUI = (() => {
  function cloneSelectFromTemplate(template) {
    const novo = document.createElement("select");
    novo.name = template.name;
    novo.className = template.className;
    if (template.required) novo.required = true;
    novo.innerHTML = template.innerHTML;
    novo.value = "";
    return novo;
  }

  function makeRemoveBtn(wrapper, countSelector, msg) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "remove-btn";
    btn.innerHTML = "−";
    btn.addEventListener("click", () => {
      if (document.querySelectorAll(countSelector).length > 1) {
        wrapper.remove();
      } else {
        alert(msg);
      }
    });
    return btn;
  }

  async function ensureSelectFilled(select, cacheKey) {
    if (select.options.length > 1) return;
    if (typeof OfflineSync === "undefined") return;
    const list = (await OfflineSync.getCachedList(cacheKey)) || [];
    if (!list.length) return;
    const ph =
      select.classList.contains("area-origem-select")
        ? "Selecione a área de origem"
        : select.classList.contains("area-destino-select")
          ? "Selecione a área de destino"
          : cacheKey === "areas"
            ? "Selecione a área"
            : "Selecione o produto";
    OfflineSync.fillSelectList(
      select,
      list,
      cacheKey === "areas" ? OfflineSync.areaOptionLabel : (item) => item.nome,
      ph
    );
  }

  function appendAreaRow(listaId, selectClass, nameAttr, countSelector, msg) {
    const lista = document.getElementById(listaId);
    if (!lista) return;
    const template = lista.querySelector(selectClass);
    if (!template) return;

    const novo = cloneSelectFromTemplate(template);
    novo.name = nameAttr;
    novo.classList.add(selectClass.replace(".", ""));

    const wrapper = document.createElement("div");
    wrapper.className = "form-box form-box-area linha";
    wrapper.appendChild(novo);
    wrapper.appendChild(makeRemoveBtn(wrapper, countSelector, msg));
    lista.appendChild(wrapper);
    ensureSelectFilled(novo, "areas");
  }

  function addAreaRow() {
    appendAreaRow(
      "lista-areas",
      ".area-select",
      "area[]",
      "#lista-areas .form-box-area, .lista-areas .form-box-area",
      "É necessário manter pelo menos uma área."
    );
  }

  function addOrigemRow() {
    appendAreaRow(
      "lista-origens",
      ".area-origem-select",
      "area_origem[]",
      "#lista-origens .form-box-area",
      "É necessário manter pelo menos uma área de origem."
    );
  }

  function addDestinoRow() {
    appendAreaRow(
      "lista-destinos",
      ".area-destino-select",
      "area_destino[]",
      "#lista-destinos .form-box-area",
      "É necessário manter pelo menos uma área de destino."
    );
  }

  function addProdutoRow() {
    const lista = document.getElementById("lista-produtos");
    if (lista) {
      const template = lista.querySelector(".produto-select");
      if (!template) return;
      const novo = cloneSelectFromTemplate(template);
      novo.name = "produto[]";
      const wrapper = document.createElement("div");
      wrapper.className = "form-box form-box-produto linha";
      wrapper.appendChild(novo);
      wrapper.appendChild(
        makeRemoveBtn(
          wrapper,
          "#lista-produtos .form-box-produto, .lista-produtos .form-box-produto",
          "É necessário manter pelo menos um produto."
        )
      );
      lista.appendChild(wrapper);
      ensureSelectFilled(novo, "produtos");
      return;
    }
    const sel = document.getElementById("produto");
    if (sel) ensureSelectFilled(sel, "produtos");
  }

  function installValidation() {
    document.addEventListener(
      "submit",
      (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        form
          .querySelectorAll('button[type="submit"], input[type="submit"]')
          .forEach((el) => el.blur());

        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopImmediatePropagation();
          form.reportValidity();
          if (typeof OfflineUI !== "undefined") {
            OfflineUI.setBanner(
              "Preencha todos os campos obrigatórios (incluindo área e produto).",
              "warn"
            );
          }
        }
      },
      true
    );
  }

  function installClickHandlers() {
    document.addEventListener(
      "click",
      (e) => {
        if (e.target.closest(".add-area")) {
          e.preventDefault();
          e.stopImmediatePropagation();
          addAreaRow();
          return;
        }
        if (e.target.closest(".add-origem")) {
          e.preventDefault();
          e.stopImmediatePropagation();
          addOrigemRow();
          return;
        }
        if (e.target.closest(".add-destino")) {
          e.preventDefault();
          e.stopImmediatePropagation();
          addDestinoRow();
          return;
        }
        if (e.target.closest(".add-produto")) {
          e.preventDefault();
          e.stopImmediatePropagation();
          addProdutoRow();
        }
      },
      true
    );
  }

  installValidation();
  installClickHandlers();

  return { addAreaRow, addOrigemRow, addDestinoRow, addProdutoRow };
})();

window.OfflineCatalogUI = OfflineCatalogUI;
