/**
 * Utilitários globais do Caderno de Campo.
 */
(function (global) {
  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  global.CadernoUtils = { escapeHtml };
  global.escapeHtml = escapeHtml;
})(typeof window !== "undefined" ? window : globalThis);
