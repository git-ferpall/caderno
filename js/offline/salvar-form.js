/**
 * Envio padronizado de apontamentos (online + fila offline).
 * Handler global garante salvamento mesmo se o JS da página falhar ao carregar.
 */
const CadernoSalvar = (() => {
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

  function defensivoBeforeSubmit(fd) {
    for (const [selId, inpId] of DEFENSIVO_PAIRS) {
      const sel = document.getElementById(selId);
      const inp = document.getElementById(inpId);
      if (sel && inp && sel.value === "outro") {
        fd.set(sel.name || selId, inp.value.trim());
      }
    }
  }

  function notify(type, message) {
    if (typeof showPopup === "function") {
      try {
        showPopup(type, message);
        return;
      } catch {
        /* fallback */
      }
    }
    if (typeof OfflineUI !== "undefined") {
      OfflineUI.setBanner(message, type === "success" ? "ok" : "warn");
    }
    alert(message);
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
      if (typeof OfflineUI !== "undefined") {
        OfflineUI.setBanner("Apontamento salvo neste aparelho. Sincroniza com internet.", "ok");
        OfflineUI.showOfflineSavedPopup();
      }
      await refreshPendingUI();
      if (typeof OfflineBackgroundSync !== "undefined") {
        OfflineBackgroundSync.register();
      }
      return {
        ok: true,
        offline: true,
        msg: "Salvo localmente. Sincroniza quando houver internet.",
      };
    } catch (e) {
      console.error("[CadernoSalvar] fila local:", e);
      return {
        ok: false,
        err: "Não foi possível gravar no aparelho. Use uma aba normal (não anônima) e «Baixar para offline» com internet.",
      };
    }
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
        notify("failed", "Preencha todos os campos obrigatórios (incluindo área e produto).");
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
          notify("success", local.msg);
          if (opts.onSuccess) opts.onSuccess(local);
          else if (opts.redirect !== false) {
            setTimeout(() => {
              window.location.href = redirect;
            }, 1200);
          }
          return;
        }
        if (local && !local.ok) {
          notify("failed", local.err);
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
        const msg =
          data.msg ||
          (data.offline
            ? "Salvo no dispositivo. Sincroniza quando houver internet."
            : "Salvo com sucesso!");
        notify("success", msg);
        if (opts.onSuccess) {
          opts.onSuccess(data);
        } else if (opts.redirect !== false) {
          setTimeout(() => {
            window.location.href = redirect;
          }, 1200);
        }
        return;
      }

      notify("failed", data.err || data.msg || "Erro ao salvar.");
    } catch (err) {
      if (!navigator.onLine || (await shouldSaveLocallyFirst())) {
        const fd = new FormData(form);
        mergeBeforeSubmit(opts)(fd);
        const local = await trySaveLocal(salvarUrl(phpFile), fd);
        if (local?.ok) {
          notify("success", local.msg);
          if (opts.redirect !== false) {
            setTimeout(() => {
              window.location.href = opts.redirect || "/home/apontamento";
            }, 1200);
          }
          return;
        }
        if (local?.err) {
          notify("failed", local.err);
          return;
        }
      }
      notify("failed", "Falha ao salvar: " + (err?.message || err));
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
          notify("success", local.msg);
          if (opts.onSuccess) opts.onSuccess(local);
          else if (opts.redirect !== false) {
            setTimeout(() => {
              window.location.href = redirect;
            }, 1200);
          }
          return local;
        }
        if (local && !local.ok) {
          notify("failed", local.err);
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
        const msg =
          data.msg ||
          (data.offline
            ? "Salvo no dispositivo. Sincroniza quando houver internet."
            : "Salvo com sucesso!");
        notify("success", msg);
        if (opts.onSuccess) {
          opts.onSuccess(data);
        } else if (opts.redirect !== false) {
          setTimeout(() => {
            window.location.href = redirect;
          }, 1200);
        }
        return data;
      }

      const err = data.err || data.msg || "Erro ao salvar.";
      notify("failed", err);
      if (opts.onError) opts.onError(data);
      return data;
    } catch (err) {
      if (!navigator.onLine) {
        const local = await trySaveLocal(url, formData);
        if (local?.ok) {
          notify("success", local.msg);
          return local;
        }
        if (local?.err) {
          notify("failed", local.err);
          if (opts.onError) opts.onError(local);
          return local;
        }
      }
      const msg = "Falha ao salvar: " + (err?.message || err);
      notify("failed", msg);
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

  return { submitForm, postFormData, salvarUrl, installGlobalSubmit };
})();

window.CadernoSalvar = CadernoSalvar;
