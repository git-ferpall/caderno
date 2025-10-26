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
      <h2 class="main-title cor-branco">Silo de Dados</h2>
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
                    </div>
                </div>
                <button class="silo-dados-btn v2" id="dado-remove">
                    <div class="btn-icon icon-trash cor-branco"></div>
                </button>
                <button class="silo-dados-btn v1" id="dado-add">
                    <div class="btn-icon icon-plus cor-branco"></div>
                </button>
            </div>
        </div>
    </div>
    </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/silo.js"></script>
    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>