document.addEventListener('DOMContentLoaded', carregarIcons);
document.addEventListener('DOMContentLoaded', verCPF);
document.addEventListener('DOMContentLoaded', verTel);
document.addEventListener('DOMContentLoaded', carregarEstados);
document.addEventListener('DOMContentLoaded', coresApt);

document.onreadystatechange = function () {
  var state = document.readyState
  var load = document.getElementById('load');

  const inputs = document.querySelectorAll(".form-tel");
  inputs.forEach(input => {
    window.intlTelInput(input, {
      initialCountry: 'BR',
      nationalMode: false,
      separateDialCode: true, // exibe o +55 separado
      utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js"
    });
  });

  document.getElementById('load-img').src = (window.innerWidth > 800) ? "/img/logo-color.png" : "/img/logo-icon.png";
  (window.innerWidth > 800) ? load.classList.add('fundo-branco') : load.classList.add('fundo-azul-grad');

  if (state == 'interactive') {
          document.getElementById('conteudo').style.visibility="hidden";
          document.getElementById('footer').style.visibility="hidden";
  } else if (state == 'complete') {
      setTimeout(function(){
          document.getElementById('interactive');
          load.classList.add('up');
          load.style.visibility="hidden";
          document.getElementById('conteudo').style.visibility="visible";
          document.getElementById('footer').style.visibility="visible";
      },1000);
  }
}

function validarSenha() {
    senha = document.getElementById('fcpass').value;
    senhaC = document.getElementById('fccpass').value;
  
    if (senha != senhaC) {
      senhaC.setCustomValidity("Senhas diferentes!");
      return false;
    } else {
      return true;
    }
}

function carregarEstados() {
  if (document.getElementById('pf-ender-uf')) {
    const estadoSelect = document.getElementById('pf-ender-uf');
    const cidadeSelect = document.getElementById('pf-ender-cid');

    // Carrega estados
    fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
      .then(response => response.json())
      .then(estados => {
        // Preenche estados
        estados.forEach((estado, index) => {
          const option = document.createElement('option');
          option.value = estado.sigla;
          option.textContent = estado.sigla;
          estadoSelect.appendChild(option);
        });

        // Seleciona o primeiro estado
        const primeiroEstado = estados[0];
        estadoSelect.value = primeiroEstado.sigla;

        // Carrega cidades do primeiro estado
        carregarCidades(primeiroEstado.sigla);
      })
      .catch(error => console.error('Erro ao carregar estados:', error));

    // Função para carregar cidades
    function carregarCidades(estadoSigla) {
      cidadeSelect.innerHTML = '';

      fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${estadoSigla}/municipios`)
        .then(response => response.json())
        .then(cidades => {
          cidades.forEach(cidade => {
            const option = document.createElement('option');
            option.value = cidade.nome;
            option.textContent = cidade.nome;
            cidadeSelect.appendChild(option);
          });

          // Seleciona a primeira cidade
          if (cidades.length > 0) {
            cidadeSelect.value = cidades[0].nome;
          }
        })
        .catch(error => console.error('Erro ao carregar cidades:', error));
    }

    // Ao mudar o estado
    estadoSelect.addEventListener('change', function () {
      const estadoSigla = this.value;
      carregarCidades(estadoSigla);
    });
  }
}


 


function toggleForm(tipo) {
  const login = document.getElementById('login-form');
  const cadastro = document.getElementById('cad-form');
  const recuperacao = document.getElementById('rec-form');

  const user = document.getElementById("fuser").value;
  const cuser = document.getElementById('fcuser');

  // Copia o usuário, se existir
  if (user.trim() !== '') cuser.value = user;

  const showLogin = tipo == 'log';
  const showCadastro = tipo == 'cad';
  const showRecuperacao = tipo == 'rec';

  if (showCadastro) {
    // Mostra a tela de Cadastro e esconde as telas de Login e Recuperação de Senha
    login.classList.remove('show');
    recuperacao.classList.remove('show');

    login.classList.add('slide-out-up');
    recuperacao.classList.add('slide-out-up');

    setTimeout(() => {
      login.classList.add('d-none');
      recuperacao.classList.add('d-none');

      login.classList.remove('slide-out-up');
      recuperacao.classList.remove('slide-out-up');

      // Mostra cadastro e aplica animação de entrada
      cadastro.classList.remove('d-none');
      void cadastro.offsetWidth; // força reflow
      cadastro.classList.add('slide-in-up');

      requestAnimationFrame(() => {
        cadastro.classList.remove('slide-in-up');
        cadastro.classList.add('show');
      });
    }, 600);
  } else if (showLogin) {
    // Mostra a tela de Login e esconde as telas de Cadastro e Recuperação de Senha
    cadastro.classList.remove('show');
    recuperacao.classList.remove('show');

    cadastro.classList.add('slide-out-up');
    recuperacao.classList.add('slide-out-up');

    setTimeout(() => {
      cadastro.classList.add('d-none');
      recuperacao.classList.add('d-none');

      cadastro.classList.remove('slide-out-up');
      recuperacao.classList.remove('slide-out-up');

       // Mostra login e aplica animação de entrada
      login.classList.remove('d-none');
      void login.offsetWidth; // força reflow
      login.classList.add('slide-in-up');

      requestAnimationFrame(() => {
        login.classList.remove('slide-in-up');
        login.classList.add('show');
      });
    }, 600);
  } else if (showRecuperacao) {
    // Mostra a tela de Recuperação de Senha e esconde as telas de Cadastro e Login
    cadastro.classList.remove('show');
    login.classList.remove('show');

    cadastro.classList.add('slide-out-up');
    login.classList.add('slide-out-up');

    setTimeout(() => {
      cadastro.classList.add('d-none');
      login.classList.add('d-none');

      cadastro.classList.remove('slide-out-up');
      login.classList.remove('slide-out-up');

       // Mostra recuperação de senha e aplica animação de entrada
      recuperacao.classList.remove('d-none');
      void recuperacao.offsetWidth; // força reflow
      recuperacao.classList.add('slide-in-up');

      requestAnimationFrame(() => {
        recuperacao.classList.remove('slide-in-up');
        recuperacao.classList.add('show');
      });
    }, 600);
  } else {
    console.log("Opção de formulário incorreta.");
  }
}



function carregarIcons() {
  const icones = [
    'angle', 'camera', 'check', 'close', 'dots', 'exit', 'file', 'fruit', 'home', 'img', 'pasta', 'pdf', 'pen', 'people', 'pin', 'plant', 'plus', 'silo', 'trash', 'truck', 'txt', 'upload', 'user', 'water', 'x', 'zip'
  ];

  icones.forEach(nome => {
    fetch(`../img/icon/icon-${nome}.svg`)
      .then(response => response.text())
      .then(svg => {
        document.querySelectorAll(`.icon-${nome}`).forEach(el => {
          el.innerHTML = svg;
        });
      });
  });

  const apt = [];
  for (let i = 1; i <= 20; i++) {
    apt.push(`apt${i}`);
  }

  apt.forEach(nome => {
    fetch(`../img/icon/apt/icon-${nome}.svg`)
      .then(response => response.text())
      .then(svg => {
        document.querySelectorAll(`.icon-${nome}`).forEach(el => {
          el.innerHTML = svg;
        });
      });
  });
}

function coresApt() {
  let style = document.createElement('style');
  document.head.appendChild(style);

  let css = '';

  for (let i = 1; i <= 20; i++) {
    const id = `apt${i}`;

    css += `
      .fundo-${id} {
        background-color: var(--${id}) !important;
        color: var(--branco) !important;
      }
      .cor-${id}, .cor-${id} svg {
        color: var(--${id}) !important;
      }
    `;
  }

  style.textContent = css;
}

function abrirMenu() {
  document.querySelector('.menu-principal').classList.toggle('active');
  document.querySelector('.sistema').classList.toggle('active');
}

function sair() {
    window.location.href = "../index.php";
}