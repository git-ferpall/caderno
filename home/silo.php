<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../configuracao/configuracao_funcoes.php'; // Se já não estiver no topo

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
verificaSessaoExpirada();

if (!isLogged()) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">

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
                <h2 class="main-title cor-branco">Silo de Dados</h2>
            </div>

            <div class="sistema-main silo">
                <div class="silo-info">
                    <div class="silo-info-header">
                        <h4 class="silo-info-title"><?php echo $utilizacao; ?>% utilizado</h4>
                        <h4 class="silo-info-title"><?php echo $cap_ocupada; ?>GB de <?php echo $cap_max; ?>GB</h4>
                    </div>
                    <div class="silo-info-bar" style="background: linear-gradient(to right, var(--branco) <?php echo $cap_ocupada; ?>%, transparent <?php echo $cap_ocupada; ?>%);"></div>
                </div>

                <div class="silo-dados">

                    <div class="silo-title">
                        <h2 class="silo-title-text">Arquivos</h2>
                        <span class="silo-title-subtitle">Pasta raiz</span>
                    </div>

                    <div class="silo-arquivos">
                        <div class="silo-arquivos-sign">
                            <div class="silo-item-box">
                                <div class="silo-item silo-pasta">
                                    <div class="btn-icon icon-pasta"></div>
                                    <span class="silo-item-title">Pasta de exemplo</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                            <div class="silo-item-box">
                                <div class="silo-item silo-arquivo">
                                    <div class="btn-icon icon-file"></div>
                                    <span class="silo-item-title">Arquivo de exemplo.file</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                            <div class="silo-item-box">
                                <div class="silo-item silo-arquivo">
                                    <div class="btn-icon icon-zip"></div>
                                    <span class="silo-item-title">ZIP de exemplo.zip</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                            <div class="silo-item-box">
                                <div class="silo-item silo-arquivo">
                                    <div class="btn-icon icon-txt"></div>
                                    <span class="silo-item-title">Texto de exemplo.txt</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                            <div class="silo-item-box">
                                <div class="silo-item silo-arquivo">
                                    <div class="btn-icon icon-pdf"></div>
                                    <span class="silo-item-title">PDF de exemplo.pdf</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                            <div class="silo-item-box">
                                <div class="silo-item silo-arquivo">
                                    <div class="btn-icon icon-img"></div>
                                    <span class="silo-item-title">Imagem de exemplo.img</span>
                                </div>
                                <div class="silo-item-edit icon-pen"></div>
                            </div>
                        </div>
                    </div>
                
                    <div class="silo-dados-add">
                        <div class="silo-dados-content">
                            <div class="silo-dados-content-space">
                                <button class="silo-dados-add-btn fundo-verde cor-branco" type="button" id="btn-silo-arquivo">
                                    <div class="btn-icon icon-upload cor-branco"></div>
                                    <span class="link-title">Enviar arquivo</span>
                                </button>
                                <button class="silo-dados-add-btn fundo-laranja cor-branco" type="button" id="btn-silo-pasta">
                                    <div class="btn-icon icon-pasta cor-branco"></div>
                                    <span class="link-title">Criar nova pasta</span>
                                </button>
                                <button class="silo-dados-add-btn" type="button" id="btn-silo-scan">
                                    <div class="btn-icon icon-camera cor-preto"></div>
                                    <span class="link-title">Escanear documento</span>
                                </button>
                            </div>
                        </div>
                        <button class="silo-dados-btn">
                            <div class="btn-icon icon-upload cor-branco"></div>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
    </div>
        
    <?php include '../include/footer.php' ?>
</body>
</html>