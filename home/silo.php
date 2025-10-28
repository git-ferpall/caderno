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
    <link rel="stylesheet" href="../css/silo.css">


    <link rel="icon" type="image/png" href="/img/logo-icon.png"> 
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
            
            <div class="silo-arquivos">
            <!-- Cabeçalho de navegação (breadcrumb) -->
            <div class="silo-breadcrumb" style="margin:10px 0; font-size:14px;"></div>
            <div class="silo-busca">
                <input type="text" id="siloBusca" placeholder="🔍 Buscar arquivos ou pastas...">
            </div>    
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
                        <!-- 📤 Botão de envio -->
                        <button class="silo-dados-add-btn fundo-verde cor-branco" id="btn-silo-arquivo">
                            <div class="btn-icon icon-upload cor-branco"></div>
                            <span class="link-title">Enviar arquivo</span>
                        </button>
                        <input type="file" id="inputUploadSilo" multiple style="display:none;" />

                        <!-- 📁 Nova pasta -->
                        <button class="silo-dados-add-btn fundo-laranja cor-branco" id="btn-silo-pasta">
                            <div class="btn-icon icon-pasta cor-branco"></div>
                            <span class="link-title">Criar nova pasta</span>
                        </button>

                        <!-- 📸 Escanear -->
                        <button class="silo-dados-add-btn" id="btn-silo-scan">
                            <div class="btn-icon icon-camera cor-preto"></div>
                            <span class="link-title">Escanear documento</span>
                        </button>
                    </div>
                </div>

                <button class="silo-dados-btn v1" id="dado-add">
                    <div class="btn-icon icon-plus cor-branco"></div>
                </button>
            </div>
        </div>
    </div>

    <!-- 📦 Popup de Upload -->
    <div id="uploadPopup" class="upload-popup" style="display:none;">
        <div class="upload-box">
        <h3>⬆️ Enviando arquivos...</h3>
        <div id="uploadLista"></div>
        <p id="uploadResumo" class="progress-text">Preparando...</p>
        <button id="btnCancelarUpload" style="margin-top:15px;background:#ccc;padding:6px 12px;border:none;border-radius:6px;cursor:pointer;">
            ❌ Cancelar upload
        </button>
        </div>
    </div>
    </main>
        <?php include '../include/imports.php' ?>
        <script src="../js/silo.js"></script>
        <script src="../js/silo_pasta.js"></script>
        <script src="../js/silo_mover.js"></script>
        <script src="../js/silo_upload.js"></script>
        <script src="../js/silo_busca.js"></script>
    </div>   
    <?php include '../include/footer.php' ?>
</body>
</html>