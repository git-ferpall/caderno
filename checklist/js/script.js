document.addEventListener('DOMContentLoaded', carregarIcons);
document.addEventListener('DOMContentLoaded', coresApt);


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