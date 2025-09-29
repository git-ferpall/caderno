document.addEventListener("DOMContentLoaded", () => {
  // Evita selecionar a mesma área 2x
  document.querySelectorAll("select[id*='-area']").forEach(select => {
    select.addEventListener("change", () => {
      const val = select.value;
      if (!val) return;

      document.querySelectorAll("select[id*='-area']").forEach(other => {
        if (other !== select) {
          const opt = other.querySelector(`option[value='${val}']`);
          if (opt) opt.remove(); // remove do outro select
        }
      });
    });
  });

  // Evita selecionar o mesmo produto 2x
  document.querySelectorAll("select[id*='-produto']").forEach(select => {
    select.addEventListener("change", () => {
      const val = select.value;
      if (!val) return;

      document.querySelectorAll("select[id*='-produto']").forEach(other => {
        if (other !== select) {
          const opt = other.querySelector(`option[value='${val}']`);
          if (opt) opt.remove();
        }
      });
    });
  });
});
