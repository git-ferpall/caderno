/**
 * Campo "Outro (digitar manualmente)" — fungicida, herbicida, inseticida, fertilizante.
 */
const DefensivoOutro = (() => {
  const PAIRS = [
    { selectId: "fungicida", inputId: "fungicida_outro" },
    { selectId: "herbicida", inputId: "herbicida_outro" },
    { selectId: "inseticida", inputId: "inseticida_outro" },
    { selectId: "fertilizante", inputId: "fertilizante_outro" },
  ];

  function appendOutroOption(select) {
    if (!select) return;
    if (select.querySelector('option[value="outro"]')) return;
    const outro = document.createElement("option");
    outro.value = "outro";
    outro.textContent = "Outro (digitar manualmente)";
    select.appendChild(outro);
  }

  function setOutroVisible(input, visible) {
    if (!input) return;
    input.classList.toggle("defensivo-outro-open", visible);
    input.required = visible;
    input.setAttribute("aria-hidden", visible ? "false" : "true");
    if (!visible) {
      input.value = "";
    } else {
      requestAnimationFrame(() => {
        input.focus();
        input.scrollIntoView({ block: "nearest", behavior: "smooth" });
      });
    }
  }

  function syncFromSelect(selectId) {
    const pair = PAIRS.find((p) => p.selectId === selectId);
    if (!pair) return;
    const sel = document.getElementById(pair.selectId);
    const inp = document.getElementById(pair.inputId);
    if (!sel || !inp) return;
    setOutroVisible(inp, sel.value === "outro");
  }

  function ensureAllOutroOptions() {
    PAIRS.forEach(({ selectId }) => {
      appendOutroOption(document.getElementById(selectId));
    });
  }

  function bindAll() {
    document.addEventListener(
      "change",
      (e) => {
        const sel = e.target;
        if (!(sel instanceof HTMLSelectElement)) return;
        const pair = PAIRS.find((p) => p.selectId === sel.id);
        if (!pair) return;
        const inp = document.getElementById(pair.inputId);
        setOutroVisible(inp, sel.value === "outro");
      },
      true
    );

    document.addEventListener(
      "input",
      (e) => {
        const sel = e.target;
        if (!(sel instanceof HTMLSelectElement)) return;
        const pair = PAIRS.find((p) => p.selectId === sel.id);
        if (!pair) return;
        const inp = document.getElementById(pair.inputId);
        setOutroVisible(inp, sel.value === "outro");
      },
      true
    );

    ensureAllOutroOptions();
    PAIRS.forEach(({ selectId }) => syncFromSelect(selectId));
  }

  function afterCatalogLoaded(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    appendOutroOption(sel);
    syncFromSelect(selectId);
  }

  function init() {
    bindAll();
    [500, 1500, 3000].forEach((ms) => {
      setTimeout(ensureAllOutroOptions, ms);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  return { appendOutroOption, ensureAllOutroOptions, afterCatalogLoaded, syncFromSelect };
})();

window.DefensivoOutro = DefensivoOutro;
