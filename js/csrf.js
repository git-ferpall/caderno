/**
 * Proteção CSRF (double-submit cookie) para fetch e jQuery.ajax.
 */
(function () {
  function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)csrf_token=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : "";
  }

  function withCsrfHeaders(init) {
    const token = getCsrfToken();
    if (!token) return init || {};

    const next = Object.assign({}, init || {});
    const headers = new Headers(next.headers || {});
    if (!headers.has("X-CSRF-Token")) {
      headers.set("X-CSRF-Token", token);
    }
    next.headers = headers;
    return next;
  }

  const originalXhrOpen = XMLHttpRequest.prototype.open;
  const originalXhrSend = XMLHttpRequest.prototype.send;

  XMLHttpRequest.prototype.open = function (method, ...args) {
    this._csrfMethod = String(method || "GET").toUpperCase();
    return originalXhrOpen.call(this, method, ...args);
  };

  XMLHttpRequest.prototype.send = function (body) {
    const method = this._csrfMethod || "GET";
    if (method !== "GET" && method !== "HEAD" && method !== "OPTIONS") {
      const token = getCsrfToken();
      if (token) {
        this.setRequestHeader("X-CSRF-Token", token);
      }
    }
    return originalXhrSend.call(this, body);
  };

  const originalFetch = window.fetch.bind(window);
  window.fetch = function (input, init) {
    const method = String(
      (init && init.method) ||
        (input instanceof Request ? input.method : "GET")
    ).toUpperCase();

    if (method !== "GET" && method !== "HEAD" && method !== "OPTIONS") {
      init = withCsrfHeaders(init);
    }
    return originalFetch(input, init);
  };

  function installJqueryCsrf() {
    if (!window.jQuery) return;
    window.jQuery.ajaxSetup({
      beforeSend: function (xhr, settings) {
        const method = String(settings.type || "GET").toUpperCase();
        if (method === "GET" || method === "HEAD" || method === "OPTIONS") return;
        const token = getCsrfToken();
        if (token) xhr.setRequestHeader("X-CSRF-Token", token);
      },
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", installJqueryCsrf);
  } else {
    installJqueryCsrf();
  }

  function injectCsrfIntoForms() {
    const token = getCsrfToken();
    if (!token) return;
    document.querySelectorAll('form').forEach((form) => {
      const method = String(form.getAttribute("method") || "GET").toUpperCase();
      if (method === "GET" || method === "HEAD") return;
      if (form.querySelector('input[name="csrf_token"]')) return;
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "csrf_token";
      input.value = token;
      form.appendChild(input);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", injectCsrfIntoForms);
  } else {
    injectCsrfIntoForms();
  }
})();
