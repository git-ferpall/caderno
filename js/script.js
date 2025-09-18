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


function verTel() {
  document.querySelectorAll('.form-tel').forEach(input => {
    input.addEventListener('input', function () {
      let value = this.value.replace(/\D/g, ''); // Remove tudo que não for número

      // Limita a 11 dígitos (2 DDD + 9 número)
      if (value.length > 11) value = value.slice(0, 11);

      if (value.length <= 10) {
        // Formato fixo: (99) 9999-9999
        value = value.replace(/^(\d{0,2})(\d{0,4})(\d{0,4})/, function (_, ddd, parte1, parte2) {
          let result = '';
          if (ddd) result += '(' + ddd;
          if (ddd && ddd.length === 2) result += ') ';
          if (parte1) result += parte1;
          if (parte1 && parte1.length === 4) result += '-';
          if (parte2) result += parte2;
          else result += '';
          return result;
        });
      } else {
        // Formato celular: (99) 99999-9999
        value = value.replace(/^(\d{0,2})(\d{0,5})(\d{0,4})/, function (_, ddd, parte1, parte2) {
          let result = '';
          if (ddd) result += '(' + ddd;
          if (ddd && ddd.length === 2) result += ') ';
          if (parte1) result += parte1;
          if (parte1 && parte1.length === 5) result += '-';
          if (parte2) result += parte2;
          return result;
        });
      }

      this.value = value;
    });
  });

  document.querySelectorAll('.only-num').forEach(input => {
    // Impede letras ou símbolos
    input.addEventListener('keypress', function (e) {
      if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
      }
    });
  });
}

function verCPF() {
  if (document.getElementById('pf-cpf')) {
    document.getElementById('pf-cpf').addEventListener('input', function (e) {
      let value = this.value.replace(/\D/g, ''); // Remove tudo que não for número

      // Limita a 11 dígitos
      if (value.length > 11) value = value.slice(0, 11);

      // Aplica a máscara
      if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
      } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
      } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
      }

      this.value = value;
    });
  }

  if (document.getElementById('pf-cnpj')) {
    document.getElementById('pf-cnpj').addEventListener('input', function (e) {
      let value = this.value.replace(/\D/g, ''); // Remove tudo que não for número

      // Limita a 14 dígitos
      if (value.length > 14) value = value.slice(0, 14);

      // Aplica a máscara
      if (value.length > 12) {
        value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
      } else if (value.length > 8) {
        value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
      } else if (value.length > 5) {
        value = value.replace(/(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
      } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{1,3})/, '$1.$2');
      }

      this.value = value;
    });
  }

  if (document.getElementById('pf-tipo')) {
    const selectTipo = document.getElementById('pf-tipo');
    const inputCNPJ = document.getElementById('pf-cnpj');
    const inputCPF = document.getElementById('pf-cpf');

    function atualizarCamposDocumento() {
      if (selectTipo.value === 'cpf') {
        inputCPF.classList.remove('d-none');
        inputCNPJ.classList.add('d-none');
        inputCNPJ.value = ''; // limpa o outro campo
      } else {
        inputCNPJ.classList.remove('d-none');
        inputCPF.classList.add('d-none');
        inputCPF.value = ''; // limpa o outro campo
      }
    }

    // Executa uma vez ao carregar a página
    atualizarCamposDocumento();

    // Atualiza ao mudar o select
    selectTipo.addEventListener('change', atualizarCamposDocumento);
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
    'angle', 'camera', 'check', 'close', 'dots', 'exit', 'file', 'fruit', 'home', 'img', 'pasta', 'pdf', 'pen', 'people', 'pin', 'plant', 'plus', 'silo', 'truck', 'txt', 'upload', 'user', 'water', 'x', 'zip'
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
    const nome = apt.nome;

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

function abrirMenu() {
  document.querySelector('.menu-principal').classList.toggle('active');
  document.querySelector('.sistema').classList.toggle('active');
}

function sair() {
    window.location.href = "../index.php";
}