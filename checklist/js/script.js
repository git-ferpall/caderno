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

  
function validaDados() {
  const user = document.getElementById("fuser").value.trim();
  const pass = document.getElementById("fpass").value.trim();
  const form = document.getElementById('flogin');

  if (user && pass) {
    form.action = "/home/home.php";
    form.submit();
  } else {
    alert("Preencha todos os campos antes de entrar.");
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

function enviarRecuperacao() {
  const recuperacao = document.getElementById("frec").value;

  if(recuperacao.trim() !== '') {
    const loginBox = document.getElementById('login-box-id');
    const confirmaBox = document.getElementById('confirma-box-id');

    // Esconde login com animação
    loginBox.classList.remove('show');
    loginBox.classList.add('slide-out-up');

    setTimeout(() => {
      // Após animação de saída
      loginBox.classList.add('d-none');
      loginBox.classList.remove('slide-out-up');

      // Mostra confirma com animação de entrada
      confirmaBox.classList.remove('d-none');
      void confirmaBox.offsetWidth; // força reflow
      confirmaBox.classList.add('slide-in-up');

      requestAnimationFrame(() => {
        confirmaBox.classList.remove('slide-in-up');
        confirmaBox.classList.add('show');
      });

      // Aguarda 5 segundos e volta para o login
      setTimeout(() => {
        confirmaBox.classList.remove('show');
        confirmaBox.classList.add('slide-out-up');

        setTimeout(() => {
          confirmaBox.classList.add('d-none');
          confirmaBox.classList.remove('slide-out-up');

          loginBox.classList.remove('d-none');
          void loginBox.offsetWidth; // força reflow
          loginBox.classList.add('slide-in-up');

          requestAnimationFrame(() => {
            loginBox.classList.remove('slide-in-up');
            loginBox.classList.add('show');
            toggleForm('log');
          });
        }, 500); // Tempo da animação de saída

      }, 5000); // Espera 5 segundos com a tela de confirmação

    }, 500); // Tempo da animação de saída do login
  } else {
    console.log('Informe o email ou número de telefone para que seja enviada a solicitação de redefinição de senha.');
  };
}

function toggleSenha(btn) {
  const input = btn.closest('.form-senha').querySelector('input');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'Ocultar'; // ou use um ícone de olho fechado
  } else {
    input.type = 'password';
    btn.textContent = 'Ver';
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

function novoApontamento(apt) {
    const id = '#apt' + apt.id;
    const idAdd = '#add-apt' + apt.id;

    if ($(id).hasClass('active')) {

      $(id).removeClass('active');
      $('.apt-button').removeClass('d-none');
      $(idAdd).addClass('d-none');

    } else {

      $('.apt-button').addClass('d-none');
      $(id).removeClass('d-none');
      $(id).addClass('active');
      $(idAdd).removeClass('d-none');

    }
}

function selectEstufa(estufa) {
  const id = '#estufa-' + estufa;
  const idBtn = '#edit-estufa-' + estufa;
  const addEstufa = '#add-estufa';
  const estufaContent = '#estufa-' + estufa + '-box';

  if ($(id).hasClass('active')) {

      $(id).removeClass('active');
      $('.item-estufa').removeClass('d-none');
      $(addEstufa).removeClass('d-none');
      $(estufaContent).addClass('d-none');
      $(idBtn).text('Selecionar');

  } else {

      $('.item-estufa').addClass('d-none');
      $(id).removeClass('d-none');
      $(id).addClass('active');
      $(addEstufa).addClass('d-none');
      $(estufaContent).removeClass('d-none');
      $(idBtn).text('Alterar');

  }
}

function selectBancada(bancada, estufa) {
  const id = '#estufa-' + estufa;
  const header = id + '-box .item-estufa-header';
  const btn = '#item-bancada-' + String(bancada) + '-estufa-' + String(estufa);
  const content = '#item-bancada-' + String(bancada) + '-content-estufa-' + String(estufa);
  const novaBancada = '#add-bancada-estufa-' + String(estufa);

  if ($(btn).hasClass('active')) {

      $('.item-bancada').removeClass('d-none');
      $(btn).removeClass('active');
      $('.item-bancada-content').addClass('d-none');
      $(novaBancada).removeClass('d-none');
      $(id).removeClass('d-none');
      $(header).removeClass('d-none');

  } else {

      $('.item-bancada').addClass('d-none');
      $(btn).removeClass('d-none');
      $(btn).addClass('active');
      $(content).removeClass('d-none');
      $(novaBancada).addClass('d-none');
      $(id).addClass('d-none');
      $(header).addClass('d-none');

  }
}

function voltarEstufa(estufa) {
  const id = '#estufa-' + estufa;
  const novaBancada = '#add-bancada-estufa-' + estufa;
  $('.item-bancada').removeClass('d-none');
  $('.item-bancada').removeClass('active');
  $('.item-bancada-content').addClass('d-none');
  $('.item-estufa-header').removeClass('d-none');
  $(id).removeClass('d-none');
  $(novaBancada).removeClass('d-none');
}

function abrirMenu() {
  document.querySelector('.menu-principal').classList.toggle('active');
  document.querySelector('.sistema').classList.toggle('active');
}

function sair() {
    window.location.href = "../index.php";
}