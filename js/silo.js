document.addEventListener('DOMContentLoaded', () => {
  atualizarLista();
  atualizarUso();

  // Enviar arquivo normal
  document.getElementById('btn-silo-arquivo').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,text/plain';
    input.onchange = () => enviarArquivo(input.files[0]);
    input.click();
  });

  // Escanear (foto da câmera)
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

  try {
    const res = await fetch('../funcoes/silo/upload_arquivo.php', {
      method: 'POST',
      body: fd
    });

    const text = await res.text();
    console.log('Resposta upload:', text);

    let j;
    try { j = JSON.parse(text); }
    catch { throw new Error('Retorno inválido do servidor'); }

    if (j.ok) {
      alert('✅ Arquivo enviado com sucesso!');
      atualizarLista();
      atualizarUso();
    } else {
      alert('❌ Erro ao enviar: ' + j.err);
    }

  } catch (e) {
    alert('❌ Falha: ' + e.message);
  }
}

async function atualizarLista() {
  try {
    const res = await fetch('../funcoes/silo/listar_arquivos.php');
    const text = await res.text();
    console.log('Resposta listar:', text);

    let j;
    try { j = JSON.parse(text); }
    catch { throw new Error('JSON inválido'); }

    const box = document.querySelector('.silo-arquivos');
    box.innerHTML = '';

    if (j.ok && Array.isArray(j.arquivos) && j.arquivos.length > 0) {
      j.arquivos.forEach(a => {
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
    } else {
      box.innerHTML = `
        <div class="silo-item-box">
          <div class="silo-item">
            <span class="silo-item-title cor-cinza">Nenhum arquivo encontrado</span>
          </div>
        </div>
      `;
    }
  } catch (e) {
    alert('Erro ao listar: ' + e.message);
  }
}

async function excluirArquivo(id) {
  if (!confirm('Excluir este arquivo?')) return;
  const fd = new FormData();
  fd.append('id', id);

  try {
    const res = await fetch('../funcoes/silo/excluir_arquivo.php', { method: 'POST', body: fd });
    const text = await res.text();
    console.log('Resposta excluir:', text);

    const j = JSON.parse(text);
    if (j.ok) {
      alert('🗑️ Arquivo removido!');
      atualizarLista();
      atualizarUso();
    } else {
      alert('Erro: ' + j.err);
    }
  } catch (e) {
    alert('Falha ao excluir: ' + e.message);
  }
}

async function atualizarUso() {
  try {
    const res = await fetch('../funcoes/silo/get_uso.php');
    const text = await res.text();
    console.log('Resposta uso:', text);

    const j = JSON.parse(text);
    if (j.ok) {
      const percent = j.percent ?? 0;
      document.querySelector('.silo-info-title').innerText =
        `${percent}% utilizado (${j.usado_gb} GB de ${j.limite_gb} GB)`;
      document.querySelector('.silo-info-bar').style.background =
        `linear-gradient(to right, var(--verde) ${percent}%, transparent ${percent}%)`;
    }
  } catch (e) {
    console.error('Erro ao obter uso:', e);
  }
}
