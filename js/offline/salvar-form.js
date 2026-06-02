/**
 * Envio padronizado de apontamentos (online + fila offline).
 * Handler global garante salvamento mesmo se o JS da página falhar ao carregar.
 */
const CadernoSalvar = (() => {
  const PLANTIO_FORM_ID = "form-plantio";

  const FORM_ENDPOINTS = {
    "form-fungicida": "salvar_fungicida.php",
    "form-herbicida": "salvar_herbicida.php",
    "form-inseticida": "salvar_inseticida.php",
    "form-fertilizante": "salvar_fertilizante.php",
    "form-adubacao-calcario": "salvar_adubacao_calcario.php",
    "form-adubacao-organica": "salvar_adubacao_organica.php",
    "form-clima": "salvar_clima.php",
    "form-colheita": "salvar_colheita.php",
    "form-controle-agua": "salvar_controle_agua.php",
    "form-coleta": "salvar_coleta_analise.php",
    "form-erradicacao": "salvar_erradicacao.php",
    "form-irrigacao": "salvar_irrigacao.php",
    "form-manejo": "salvar_manejo_integrado.php",
    "form-moscas": "salvar_moscas_frutas.php",
    "form-personalizado": "salvar_personalizado.php",
    "form-pragas": "salvar_pragas_doencas.php",
    "form-revisao": "salvar_revisao_maquinas.php",
    "form-transplantio": "salvar_transplantio.php",
    "form-visita": "salvar_visita_tecnica.php",
  };

  const DEFENSIVO_PAIRS = [
    ["fungicida", "fungicida_outro"],
    ["herbicida", "herbicida_outro"],
    ["inseticida", "inseticida_outro"],
    ["fertilizante", "fertilizante_outro"],
  ];

  let globalInstalled = false;

  function salvarUrl(phpFile) {
    const file = phpFile.includes("/") ? phpFile.split("/").pop() : phpFile;
    return `/funcoes/${file}`;
  }

  function resolveEndpoint(form) {
    if (!form) return null;
    if (form.dataset.salvar) return form.dataset.salvar;
    if (form.id && FORM_ENDPOINTS[form.id]) return FORM_ENDPOINTS[form.id];
    return null;
  }

  function hideOverlayPopups() {
    document.querySelectorAll("#popup-overlay .popup-box").forEach((el) => el.classList.add("d-none"));
  }

  function ensurePlantioConfirmPopup() {
    const overlay = document.getElementById("popup-overlay");
    if (!overlay) return null;

    let popup = document.getElementById("popup-confirm-plantio");
    if (!popup) {
      popup = document.createElement("div");
      popup.className = "popup-box d-none";
      popup.id = "popup-confirm-plantio";
      popup.innerHTML = `
        <h2 class="popup-title">Gerar também colheita?</h2>
        <p class="popup-text">
          Deseja que seja criado automaticamente um apontamento de <b>colheita</b>
          com status <b>PENDENTE</b> para este plantio?
        </p>
        <div class="popup-actions">
          <button type="button" class="popup-btn fundo-cinza-b cor-preto" id="btn-plantio-colheita-nao">Não</button>
          <button type="button" class="popup-btn fundo-verde" id="btn-plantio-colheita-sim">Sim</button>
        </div>
      `;
      overlay.appendChild(popup);
    }
    return { overlay, popup };
  }

  /** Pergunta se gera colheita pendente junto com o plantio. */
  function askPlantioColheita() {
    return new Promise((resolve) => {
      const ref = ensurePlantioConfirmPopup();
      if (!ref) {
        resolve(
          window.confirm(
            "Deseja gerar também um apontamento de colheita PENDENTE para este plantio?"
          )
        );
        return;
      }

      const { overlay, popup } = ref;
      hideOverlayPopups();
      overlay.classList.remove("d-none");
      popup.classList.remove("d-none");

      const finish = (value) => {
        overlay.classList.add("d-none");
        popup.classList.add("d-none");
        resolve(value);
      };

      const btnSim = popup.querySelector("#btn-plantio-colheita-sim");
      const btnNao = popup.querySelector("#btn-plantio-colheita-nao");
      if (btnSim) btnSim.onclick = () => finish(true);
      if (btnNao) btnNao.onclick = () => finish(false);
    });
  }

  /**
   * Plantio: validação + popup colheita + fila offline (não depende de plantio.js).
   */
  async function submitPlantio(form, opts = {}) {
    if (!form || form.dataset.saving === "1") return;

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => el.blur());

    if (!form.checkValidity()) {
      form.reportValidity();
      notifyFailed("Preencha todos os campos obrigatórios (data, área, produto e previsão).");
      return;
    }

    const incluirColheita = await askPlantioColheita();

    form.dataset.saving = "1";
    setSubmitBusy(form, true);

    try {
      const fd = new FormData(form);
      fd.append("incluir_colheita", incluirColheita ? "1" : "0");
      await postFormData("salvar_plantio.php", fd, opts);
    } finally {
      form.dataset.saving = "0";
      setSubmitBusy(form, false);
    }
  }

  function defensivoBeforeSubmit(fd) {
    for (const [selId, inpId] of DEFENSIVO_PAIRS) {
      const sel = document.getElementById(selId);
      const inp = document.getElementById(inpId);
      if (sel && inp && sel.value === "outro") {
        fd.set(sel.name || selId, inp.value.trim());
      }
    }
  }

  function notifySuccess(isOffline, onOk) {
    if (typeof OfflineUI !== "undefined" && OfflineUI.showSuccessPopup) {
      OfflineUI.showSuccessPopup(isOffline ? "offline" : "online", onOk);
      return;
    }
    const msg = isOffline
      ? "Apontamento salvo com sucesso! Será enviado quando houver internet."
      : "Dados salvos com sucesso!";
    if (typeof showPopup === "function") {
      try {
        showPopup("success", msg);
        if (onOk) setTimeout(onOk, 400);
        return;
      } catch {
        /* fallback */
      }
    }
    alert(msg);
    if (onOk) onOk();
  }

  function notifyFailed(message) {
    if (typeof OfflineUI !== "undefined" && OfflineUI.showFailedPopup) {
      OfflineUI.showFailedPopup(
        message || "Verifique se todos os campos estão preenchidos e tente novamente."
      );
      return;
    }
    if (typeof showPopup === "function") {
      try {
        showPopup("failed", message);
        return;
      } catch {
        /* fallback */
      }
    }
    alert(message || "Não foi possível salvar.");
  }

  function setSubmitBusy(form, busy) {
    if (!form) return;
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => {
      el.disabled = busy;
      if (!busy) el.blur();
    });
  }

  async function refreshPendingUI() {
    if (typeof OfflineDB === "undefined") return;
    try {
      const n = await OfflineDB.countFila();
      if (typeof OfflineUI !== "undefined") await OfflineUI.updateBadge(n);
      if (typeof OfflinePendingPanel !== "undefined") await OfflinePendingPanel.refresh();
    } catch {
      /* ignore */
    }
  }

  async function trySaveLocal(url, formData) {
    if (typeof OfflineSync === "undefined" || typeof OfflineDB === "undefined") {
      return null;
    }
    try {
      await OfflineSync.enqueue(url, formData);
      await refreshPendingUI();
      if (typeof OfflineBackgroundSync !== "undefined") {
        OfflineBackgroundSync.register();
      }
      return {
        ok: true,
        offline: true,
      };
    } catch (e) {
      console.error("[CadernoSalvar] fila local:", e);
      return {
        ok: false,
        err: "Não foi possível gravar no aparelho. Use uma aba normal (não anônima) e «Baixar para offline» com internet.",
      };
    }
  }

  function afterSaveSuccess(data, opts, redirect) {
    const isOffline = !!(data && data.offline);
    const doRedirect = opts.redirect !== false && !opts.onSuccess;
    const go = doRedirect
      ? () => {
          window.location.href = redirect;
        }
      : undefined;

    if (isOffline && typeof OfflineUI !== "undefined") {
      OfflineUI.setBanner("Apontamento salvo neste aparelho.", "ok");
      OfflineUI.showOfflineSavedPopup(go);
    } else {
      notifySuccess(isOffline, go);
    }

    if (opts.onSuccess) opts.onSuccess(data);
  }

  async function shouldSaveLocallyFirst() {
    if (!navigator.onLine) return true;
    if (typeof OfflineConnectivity === "undefined") return false;
    try {
      return !(await OfflineConnectivity.hasServerReachable());
    } catch {
      return false;
    }
  }

  function mergeBeforeSubmit(opts = {}) {
    const extra = opts.beforeSubmit;
    return (fd) => {
      defensivoBeforeSubmit(fd);
      if (typeof extra === "function") extra(fd);
    };
  }

  /**
   * @param {HTMLFormElement} form
   * @param {string} phpFile ex: salvar_fungicida.php
   * @param {{ beforeSubmit?: (fd: FormData) => void, redirect?: string, onSuccess?: Function }} [opts]
   */
  async function submitForm(form, phpFile, opts = {}) {
    if (!form) return;

    if (form.dataset.saving === "1") return;
    form.dataset.saving = "1";
    setSubmitBusy(form, true);

    try {
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => el.blur());

      if (!form.checkValidity()) {
        form.reportValidity();
        notifyFailed("Preencha todos os campos obrigatórios (incluindo área e produto).");
        return;
      }

      const fd = new FormData(form);
      const beforeSubmit = mergeBeforeSubmit(opts);
      beforeSubmit(fd);

      const url = salvarUrl(phpFile);
      const redirect = opts.redirect !== undefined ? opts.redirect : "/home/apontamento";

      if (await shouldSaveLocallyFirst()) {
        const local = await trySaveLocal(url, fd);
        if (local?.ok) {
          afterSaveSuccess(local, opts, redirect);
          return;
        }
        if (local && !local.ok) {
          notifyFailed(local.err);
          return;
        }
      }

      const resp = await fetch(url, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
      });
      const data = await resp.json().catch(() => ({}));

      if (data.ok) {
        afterSaveSuccess(data, opts, redirect);
        return;
      }

      notifyFailed(data.err || data.msg || "Erro ao salvar.");
    } catch (err) {
      if (!navigator.onLine || (await shouldSaveLocallyFirst())) {
        const fd = new FormData(form);
        mergeBeforeSubmit(opts)(fd);
        const local = await trySaveLocal(salvarUrl(phpFile), fd);
        if (local?.ok) {
          afterSaveSuccess(local, opts, opts.redirect || "/home/apontamento");
          return;
        }
        if (local?.err) {
          notifyFailed(local.err);
          return;
        }
      }
      notifyFailed("Falha ao salvar: " + (err?.message || err));
    } finally {
      form.dataset.saving = "0";
      setSubmitBusy(form, false);
    }
  }

  /**
   * POST com FormData já montado (ex.: plantio + incluir_colheita).
   */
  async function postFormData(phpFile, formData, opts = {}) {
    const url = salvarUrl(phpFile);
    const redirect = opts.redirect || "/home/apontamento";

    try {
      if (await shouldSaveLocallyFirst()) {
        const local = await trySaveLocal(url, formData);
        if (local?.ok) {
          afterSaveSuccess(local, opts, redirect);
          return local;
        }
        if (local && !local.ok) {
          notifyFailed(local.err);
          if (opts.onError) opts.onError(local);
          return local;
        }
      }

      const resp = await fetch(url, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });
      const data = await resp.json().catch(() => ({}));

      if (data.ok) {
        afterSaveSuccess(data, opts, redirect);
        return data;
      }

      const err = data.err || data.msg || "Erro ao salvar.";
      notifyFailed(err);
      if (opts.onError) opts.onError(data);
      return data;
    } catch (err) {
      if (!navigator.onLine || (await shouldSaveLocallyFirst())) {
        const local = await trySaveLocal(url, formData);
        if (local?.ok) {
          afterSaveSuccess(local, opts, redirect);
          return local;
        }
        if (local?.err) {
          notifyFailed(local.err);
          if (opts.onError) opts.onError(local);
          return local;
        }
      }
      const msg = "Falha ao salvar: " + (err?.message || err);
      notifyFailed(msg);
      if (opts.onError) opts.onError(err);
      throw err;
    }
  }

  function installGlobalSubmit() {
    if (globalInstalled) return;
    globalInstalled = true;

    document.addEventListener(
      "submit",
      (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.classList.contains("main-form")) return;

        if (form.id === PLANTIO_FORM_ID) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          void submitPlantio(form);
          return;
        }

        const php = resolveEndpoint(form);
        if (!php) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        submitForm(form, php);
      },
      true
    );
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", installGlobalSubmit);
  } else {
    installGlobalSubmit();
  }

  return { submitForm, submitPlantio, postFormData, salvarUrl, installGlobalSubmit };
})();

window.CadernoSalvar = CadernoSalvar;
