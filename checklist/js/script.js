/* =========================================================
   UTILIDADES
========================================================= */
function qs(selector, scope = document) {
  return scope.querySelector(selector);
}

function qsa(selector, scope = document) {
  return [...scope.querySelectorAll(selector)];
}

/* =========================================================
   DOM READY
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  initLoader();
  initTelefone();
  initCPF_CNPJ();
  initEstados();
  initIcons();
  gerarCoresApt();
});

/* =========================================================
   LOADER / SPLASH
========================================================= */
function initLoader() {
  const load = qs('#load');
  const loadImg = qs('#load-img');
  const conteudo = qs('#conteudo');
  const footer = qs('#footer');

  if (!load || !loadImg) return;

  loadImg.src = window.innerWidth > 800
    ? "/img/logo-color.png"
    : "/img/logo-icon.png";

  load.classList.add(
    window.innerWidth > 800 ? 'fundo-branco' : 'fundo-azul-grad'
  );

  document.onreadystatechange = () => {
    if (document.readyState === 'interactive') {
      if (conteudo) conteudo.style.visibility = 'hidden';
      if (footer) footer.style.visibility = 'hidden';
    }

    if (document.readyState === 'complete') {
      setTimeout(() => {
        load.classList.add('up');
        load.style.visibility = 'hidden';
        if (conteudo) conteudo.style.visibility = 'visible';
        if (footer) footer.style.visibility = 'visible';
      }, 800);
    }
  };
}

/* =========================================================
   TELEFONE
========================================================= */
function initTelefone() {
  qsa('.form-tel').forEach(input => {
    if (window.intlTelInput) {
      window.intlTelInput(input, {
        initialCountry: 'BR',
        nationalMode: false,
        separateDialCode: true,
        utilsScript:
          "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js"
      });
    }

    input.addEventListener('input', () => {
      let v = input.value.replace(/\D/g, '').slice(0, 11);

      v = v.length <= 10
        ? v.replace(/^(\d{0,2})(\d{0,4})(\d{0,4})/, (_, a, b, c) =>
            `${a ? '(' + a : ''}${a?.length === 2 ? ') ' : ''}${b}${b?.length === 4 ? '-' : ''}${c}`
          )
        : v.replace(/^(\d{0,2})(\d{0,5})(\d{0,4})/, (_, a, b, c) =>
            `${a ? '(' + a : ''}${a?.length === 2 ? ') ' : ''}${b}${b?.length === 5 ? '-' : ''}${c}`
          );

      input.value = v;
    });
  });

  qsa('.only-num').forEach(input => {
    input.addEventListener('keypress', e => {
      if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
  });
}

/* =========================================================
   CPF / CNPJ
========================================================= */
function initCPF_CNPJ() {
  const cpf = qs('#pf-cpf');
  const cnpj = qs('#pf-cnpj');
  const tipo = qs('#pf-tipo');

  if (cpf) {
    cpf.addEventListener('input', () => {
      let v = cpf.value.replace(/\D/g, '').slice(0, 11);
      cpf.value =
        v.length > 9 ? v.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4') :
        v.length > 6 ? v.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3') :
        v.length > 3 ? v.replace(/(\d{3})(\d+)/, '$1.$2') : v;
    });
  }

  if (cnpj) {
    cnpj.addEventListener('input', () => {
      let v = cnpj.value.replace(/\D/g, '').slice(0, 14);
      cnpj.value =
        v.length > 12 ? v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d+)/, '$1.$2.$3/$4-$5') :
        v.length > 8 ? v.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4') :
        v.length > 5 ? v.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3') :
        v.length > 2 ? v.replace(/(\d{2})(\d+)/, '$1.$2') : v;
    });
  }

  if (tipo && cpf && cnpj) {
    const toggle = () => {
      cpf.classList.toggle('d-none', tipo.value !== 'cpf');
      cnpj.classList.toggle('d-none', tipo.value === 'cpf');
      if (tipo.value === 'cpf') cnpj.value = '';
      else cpf.value = '';
    };
    toggle();
    tipo.addEventListener('change', toggle);
  }
}

/* =========================================================
   ESTADOS / CIDADES (IBGE)
========================================================= */
function initEstados() {
  const uf = qs('#pf-ender-uf');
  const cid = qs('#pf-ender-cid');
  if (!uf || !cid) return;

  fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
    .then(r => r.json())
    .then(estados => {
      estados.forEach(e => {
        uf.add(new Option(e.sigla, e.sigla));
      });
      carregarCidades(uf.value);
    });

  uf.addEventListener('change', () => carregarCidades(uf.value));

  function carregarCidades(sigla) {
    cid.innerHTML = '';
    fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${sigla}/municipios`)
      .then(r => r.json())
      .then(cidades => {
        cidades.forEach(c => cid.add(new Option(c.nome, c.nome)));
      });
  }
}

/* =========================================================
   ÍCONES SVG
========================================================= */
function initIcons() {
  const icons = [
    'angle','camera','check','close','dots','exit','file','fruit',
    'home','img','pasta','pdf','pen','people','pin','plant','plus',
    'silo','trash','truck','txt','upload','user','water','x','zip'
  ];

  icons.forEach(n => {
    fetch(`../img/icon/icon-${n}.svg`)
      .then(r => r.text())
      .then(svg => qsa(`.icon-${n}`).forEach(el => el.innerHTML = svg));
  });
}

/* =========================================================
   CORES APT
========================================================= */
function gerarCoresApt() {
  const style = document.createElement('style');
  document.head.appendChild(style);

  let css = '';
  for (let i = 1; i <= 20; i++) {
    css += `
      .fundo-apt${i}{background:var(--apt${i})!important;color:#fff}
      .cor-apt${i},.cor-apt${i} svg{color:var(--apt${i})!important}
    `;
  }
  style.textContent = css;
}

/* =========================================================
   MENU
========================================================= */
function abrirMenu() {
  qs('.menu-principal')?.classList.toggle('active');
  qs('.sistema')?.classList.toggle('active');
}

function sair() {
  location.href = "../index.php";
}
