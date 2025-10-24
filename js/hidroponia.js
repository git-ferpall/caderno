/**
 * Funções de controle do módulo HIDROPONIA
 * - Salvar estufas e bancadas
 * - Remover estufas e bancadas
 * - Recarregar dados após ações
 * Autor: Ferpall Tecnologia / Frutag
 */

console.log("hidroponia.js carregado ✅");

// 🔹 Salvar nova estufa
function salvarEstufa(area_id) {
  const formData = new FormData();
  formData.append('area_id', area_id);
  formData.append('nome', document.getElementById('e-nome').value);
  formData.append('area_m2', document.getElementById('e-area').value);
  formData.append('obs', document.getElementById('e-obs').value);

  fetch('../funcoes/salvar_estufa.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(d => {
    if (d.ok) {
      alert(d.msg);
      location.reload();
    } else {
      alert("Erro: " + d.err);
    }
  })
  .catch(err => console.error("Erro ao salvar estufa:", err));
}

// 🔹 Salvar nova bancada
function salvarBancada(estufa_id) {
  const formData = new FormData();
  formData.append('estufa_id', estufa_id);
  formData.append('nome', document.getElementById('b-nome').value);
  formData.append('cultura', document.getElementById('b-area').value);
  formData.append('obs', document.getElementById('b-obs').value);

  fetch('../funcoes/salvar_bancada.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(d => {
    if (d.ok) {
      alert(d.msg);
      location.reload();
    } else {
      alert("Erro: " + d.err);
    }
  })
  .catch(err => console.error("Erro ao salvar bancada:", err));
}

// 🔹 Remover estufa (e bancadas)
function removerEstufa(estufa_id) {
  if (!confirm("Tem certeza que deseja excluir esta estufa e todas as bancadas?")) return;

  const formData = new FormData();
  formData.append('estufa_id', estufa_id);

  fetch('../funcoes/remover_estufa.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(d => {
    if (d.ok) {
      alert(d.msg);
      location.reload();
    } else {
      alert("Erro: " + d.err);
    }
  })
  .catch(err => console.error("Erro ao remover estufa:", err));
}

// 🔹 Remover bancada isolada
function removerBancada(bancada_id) {
  if (!confirm("Tem certeza que deseja excluir esta bancada?")) return;

  const formData = new FormData();
  formData.append('bancada_id', bancada_id);

  fetch('../funcoes/remover_bancada.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(d => {
    if (d.ok) {
      alert(d.msg);
      location.reload();
    } else {
      alert("Erro: " + d.err);
    }
  })
  .catch(err => console.error("Erro ao remover bancada:", err));
}

// 🔹 (Opcional) Recarregar dados dinamicamente sem reload
function recarregarHidroponia() {
  fetch('../funcoes/carregar_hidroponia.php')
    .then(res => res.json())
    .then(data => {
      if (data.ok) {
        console.log("Áreas carregadas:", data.areas);
        // Aqui futuramente podemos montar o HTML dinâmico
      } else {
        alert(data.err);
      }
    });
}
