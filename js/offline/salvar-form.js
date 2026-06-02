/**
 * Envio padronizado de apontamentos (online + fila offline via fetch patch).
 */
const CadernoSalvar = (() => {
  function salvarUrl(phpFile) {
    const file = phpFile.includes("/") ? phpFile.split("/").pop() : phpFile;
    return `/funcoes/${file}`;
  }

  /**
   * @param {HTMLFormElement} form
   * @param {string} phpFile ex: salvar_fungicida.php
   * @param {{ beforeSubmit?: (fd: FormData) => void, redirect?: string }} [opts]
   */
  async function submitForm(form, phpFile, opts = {}) {
    if (!form) return;

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => {
      el.blur();
    });

    if (!form.checkValidity()) {
      form.reportValidity();
      if (typeof showPopup === "function") {
        showPopup("failed", "Preencha todos os campos obrigatórios.");
      } else if (typeof OfflineUI !== "undefined") {
        OfflineUI.setBanner("Preencha todos os campos obrigatórios.", "warn");
      }
      return;
    }

    const fd = new FormData(form);
    if (typeof opts.beforeSubmit === "function") {
      opts.beforeSubmit(fd);
    }

    const url = salvarUrl(phpFile);
    const redirect = opts.redirect || "/home/apontamento";

    try {
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
        if (typeof showPopup === "function") {
          showPopup("success", msg);
        }
        if (opts.onSuccess) {
          opts.onSuccess(data);
        } else if (opts.redirect !== false) {
          setTimeout(() => {
            window.location.href = opts.redirect || redirect;
          }, 1200);
        }
        return;
      }

      const err = data.err || data.msg || "Erro ao salvar.";
      if (typeof showPopup === "function") {
        showPopup("failed", err);
      } else {
        alert(err);
      }
    } catch (err) {
      const msg = "Falha ao salvar: " + (err?.message || err);
      if (typeof showPopup === "function") {
        showPopup("failed", msg);
      } else {
        alert(msg);
      }
    }
  }

  /**
   * POST com FormData já montado (ex.: plantio + incluir_colheita).
   */
  async function postFormData(phpFile, formData, opts = {}) {
    const url = salvarUrl(phpFile);
    const redirect = opts.redirect || "/home/apontamento";

    try {
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
        if (typeof showPopup === "function") {
          showPopup("success", msg);
        }
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
      if (typeof showPopup === "function") {
        showPopup("failed", err);
      }
      if (opts.onError) opts.onError(data);
      return data;
    } catch (err) {
      const msg = "Falha ao salvar: " + (err?.message || err);
      if (typeof showPopup === "function") {
        showPopup("failed", msg);
      }
      if (opts.onError) opts.onError(err);
      throw err;
    }
  }

  return { submitForm, postFormData, salvarUrl };
})();

window.CadernoSalvar = CadernoSalvar;
