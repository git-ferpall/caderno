document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  document.getElementById('btn-silo-arquivo').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';
    input.onchange = () => enviarArquivo(input.files[0]);
    input.click();
  });

  document.getElementById('btn-silo-scan').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.capture = 'environment';
    input.onchange = () => enviarArquivo(input.files[0], 'scan');
    input.click();
  });
});

async function enviarArquivo(file, origem = 'upload') {
  if (!file) return;
  const fd = new FormData();
  fd.append('arquivo', file);
  fd.append('origem', origem);
  const res = await fetch('../funcoes/silo/upload_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    alert('✅ Arquivo enviado!');
    atualizarLista();
    atualizarUso();
  } else {
    alert('Erro: ' + j.err);
  }
}

async function atualizarLista() {
  const res = await fetch('../funcoes/silo/listar_arquivos.php');
  const j = await res.json();
  const box = document.querySelector('.silo-arquivos');
  box.innerHTML = '';
  j.forEach(a => {
    const div = document.createElement('div');
    div.className = 'silo-item-box';
    div.innerHTML = `
      <div class="silo-item silo-arquivo">
        <div class="btn-icon icon-file"></div>
        <span class="silo-item-title">${a.nome_arquivo}</span>
      </div>
      <div class="silo-item-edit icon-trash" onclick="excluirArquivo(${a.id})"></div>
    `;
    box.appendChild(div);
  });
}

async function excluirArquivo(id) {
  if (!confirm('Excluir este arquivo?')) return;
  const fd = new FormData();
  fd.append('id', id);
  const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
  const j = await res.json();
  if (j.ok) {
    alert('🗑️ Arquivo removido!');
    atualizarLista();
    atualizarUso();
  } else {
    alert('Erro: ' + j.err);
  }
}

async function atualizarUso() {
  const res = await fetch('../funcoes/silo/get_uso.php');
  const j = await res.json();
  if (j.ok) {
    document.querySelector('.silo-info-title').innerText = `${j.percent}% utilizado (${j.usado} GB de ${j.limite} GB)`;
    document.querySelector('.silo-info-bar').style.background = `linear-gradient(to right, var(--verde) ${j.percent}%, transparent ${j.percent}%)`;
  }
}
