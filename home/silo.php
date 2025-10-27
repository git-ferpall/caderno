<?php

require_once __DIR__ . '/../configuracao/protect.php';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
<style>
    .upload-popup {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    }

    .upload-box {
    background: var(--branco);
    border-radius: 10px;
    padding: 25px 40px;
    text-align: center;
    width: 350px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    }

    .progress-bar-bg {
    width: 100%;
    height: 10px;
    background: #e1e1e1;
    border-radius: 5px;
    overflow: hidden;
    margin-top: 15px;
    }

    .progress-bar-fill {
    height: 10px;
    width: 0%;
    background: var(--verde);
    transition: width 0.2s ease;
    }

    .progress-text {
    display: block;
    margin-top: 10px;
    font-weight: 600;
    color: var(--preto);
    }
    .silo-item-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .silo-item-actions .icon-download,
    .silo-item-actions .icon-trash {
    cursor: pointer;
    font-size: 18px;
    background: none;
    border: none;
    color: var(--cor-preto);
    transition: transform 0.2s ease;
    }

    .silo-item-actions .icon-download:hover,
    .silo-item-actions .icon-trash:hover {
    transform: scale(1.2);
    }
    /* ====== MENU DE AÇÃO DO SILO ====== */
    .silo-menu-arquivo {
    position: absolute;
    background: var(--branco);
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
    padding: 8px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 130px;
    animation: fadeIn 0.15s ease-out;
    }

    .silo-menu-arquivo .menu-btn {
    border: none;
    background: none;
    text-align: left;
    font-size: 14px;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.2s;
    }

    .silo-menu-arquivo .menu-btn:hover {
    background: var(--cinza-claro);
    }

    @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
    }
    .silo-item-box.active {
    background: rgba(0, 128, 0, 0.1);
    border-radius: 10px;
    transition: background 0.2s;
    }
    .silo-item-box.active {
    background: rgba(0, 128, 0, 0.1);
    border-radius: 8px;
    }
    .btn-icon {
    width: 24px;
    height: 24px;
    display: inline-block;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    margin-right: 8px;
    vertical-align: middle;
    }

    /* 🗂️ Ícones individuais */
    .icon-pdf {
    background-image: url('../img/icon/icon-pdf.svg');
    }

    .icon-img {
    background-image: url('../img/icon/icon-img.svg');
    }

    .icon-txt {
    background-image: url('../img/icon/icon-txt.svg');
    }

    .icon-zip {
    background-image: url('../img/icon/icon-zip.svg');
    }

    .icon-x {
    background-image: url('../img/icon/icon-x.svg');
    }

    .icon-file {
    background-image: url('../img/icon/icon-file.svg');
    }
    .icon-mover::before {
    content: '\f0ec'; /* exemplo: código unicode do ícone de mover (Font Awesome) */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 16px;
    }
    .icon-folder {
    background-image: url('../img/icon/icon-pasta.svg');
    background-size: contain;
    background-repeat: no-repeat;
    }
    .silo-breadcrumb {
    color: var(--cinza-claro);
    display: flex;
    align-items: center;
    gap: 5px;
    }

    .link-voltar {
    color: var(--verde);
    cursor: pointer;
    font-weight: bold;
    }

    .link-voltar:hover {
    text-decoration: underline;
    }

    .icon-folder {
    background-image: url('../img/icon/icon-pasta.svg');
    background-size: cover;
    background-repeat: no-repeat;
    width: 32px;
    height: 32px;
    }

    /* Breadcrumb */
    .silo-breadcrumb {
    color: var(--cinza-claro);
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 10px;
    }

    .link-voltar {
    color: var(--verde);
    cursor: pointer;
    font-weight: bold;
    }

    .link-voltar:hover {
    text-decoration: underline;
    }

    /* Popup */
    .popup-sistema {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    }

    .popup-sistema .popup-box {
    background: #fff;
    padding: 20px 30px;
    border-radius: 10px;
    text-align: center;
    min-width: 280px;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }

    .popup-sistema button {
    margin-top: 10px;
    background: var(--verde);
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    }
    .icon-pasta {
    background: url('../img/icon/icon-pasta.svg') no-repeat center/contain;
    }


</style>    
</head>
<body>
    <?php require '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>
    
    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <?php 
        
        $utilizacao = 70;
        $cap_max = 100;
        $cap_atual = 30;
        $cap_ocupada = $cap_max - $cap_atual;
       
        // Insira aqui a função que vai pegar todos os arquivos e pastas do banco
        
        ?>

        <main id="silo" class="sistema">
  <div class="page-title">
      <h2 class="main-title cor-branco"></h2>
  </div>

    <div class="sistema-main silo">
        <div class="silo-info container">
            <div class="silo-info-header">
                <h4 class="silo-info-title" id="silo-uso-txt">Carregando...</h4>
            </div>
            <div class="silo-info-bar" id="silo-uso-bar"></div>
        </div>

        <div class="silo-dados">
            <!-- Cabeçalho de navegação (breadcrumb) -->
            <div class="silo-breadcrumb" style="margin:10px 0; font-size:14px;"></div>
            <div class="silo-arquivos">
                <div class="silo-arquivos-sort">
                    <button class="silo-sort-btn" type="button">
                        <span class="silo-sort-btn-text">Data</span>
                        <div class="btn-icon icon-angle"></div>
                    </button>
                    <button class="silo-sort-type" type="button">
                        <div class="btn-icon icon-silo"></div>
                    </button>
                </div>
                <!-- JS vai injetar aqui -->
            </div>

            <div class="silo-dados-add">
                <div class="silo-dados-content">
                    <div class="silo-dados-content-space">
                        <button class="silo-dados-add-btn fundo-verde cor-branco" id="btn-silo-arquivo">
                            <div class="btn-icon icon-upload cor-branco"></div>
                            <span class="link-title">Enviar arquivo</span>
                        </button>
                        <button class="silo-dados-add-btn fundo-laranja cor-branco" id="btn-silo-pasta">
                            <div class="btn-icon icon-pasta cor-branco"></div>
                            <span class="link-title">Criar nova pasta</span>
                        </button>
                        <button class="silo-dados-add-btn" id="btn-silo-scan">
                            <div class="btn-icon icon-camera cor-preto"></div>
                            <span class="link-title">Escanear documento</span>
                        </button>
                        <!-- Mover arquivo -->
                        <button class="silo-dados-add-btn fundo-laranja cor-branco" id="btn-silo-mover">
                            <div class="btn-icon icon-mover cor-branco"></div>
                            <span class="link-title">Mover arquivo</span>
                        </button>

                    </div>
                </div>
                <!--<button class="silo-dados-btn v2" id="dado-remove">
                    <div class="btn-icon icon-trash cor-branco"></div>
                </button> -->
                <button class="silo-dados-btn v1" id="dado-add">
                    <div class="btn-icon icon-plus cor-branco"></div>
                </button>
            </div>
        </div>
    </div>
    </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/silo.js"></script>
        <script src="../js/silo_pasta.js"></script>
    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>