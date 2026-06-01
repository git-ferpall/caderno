<?php

require_once __DIR__ . '/../configuracao/protect.php';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silo de Dados - Caderno de Campo</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/silo.css">
    <link rel="stylesheet" href="../css/custom/silo-page.css">

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo" class="home-layout">
        <?php include '../include/menu.php' ?>

        <main id="silo" class="sistema fundo-img silo-layout">
            <div class="silo-page">
                <div class="silo-storage-card">
                    <div class="silo-uso-circular" aria-hidden="true">
                        <span id="silo-uso-percent">0%</span>
                    </div>
                    <div class="silo-uso-text">
                        <p class="silo-uso-label">Armazenamento</p>
                        <h4 id="silo-uso-txt">Carregando...</h4>
                    </div>
                </div>

                <div class="silo-panel">
                    <div class="silo-panel-header">
                        <h2 class="silo-panel-title">Silo de Dados</h2>
                        <p class="silo-panel-subtitle">Documentos, imagens e arquivos da propriedade</p>
                    </div>

                    <div class="silo-toolbar">
                        <nav class="silo-breadcrumb" aria-label="Caminho da pasta"></nav>
                        <div class="silo-toolbar-row">
                            <div class="silo-busca">
                                <span class="silo-busca-icon" aria-hidden="true">⌕</span>
                                <input type="search" id="siloBusca" placeholder="Buscar arquivos ou pastas..." autocomplete="off">
                            </div>
                            <div class="silo-arquivos-sort">
                                <button class="silo-sort-btn" type="button">
                                    <span class="silo-sort-btn-text">Data</span>
                                    <div class="btn-icon icon-angle"></div>
                                </button>
                                <button class="silo-sort-type" type="button" title="Alternar visualização">
                                    <div class="btn-icon icon-silo"></div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="silo-arquivos-wrap">
                        <div class="silo-arquivos-grid"></div>
                    </div>

                    <div class="silo-dados-add">
                        <div class="silo-dados-content">
                            <div class="silo-dados-content-space">
                                <button class="silo-dados-add-btn silo-action-upload" id="btn-silo-arquivo" type="button">
                                    <div class="btn-icon icon-upload"></div>
                                    <span class="link-title">Enviar arquivo</span>
                                </button>
                                <input type="file" id="inputUploadSilo" multiple hidden>

                                <button class="silo-dados-add-btn silo-action-folder" id="btn-silo-pasta" type="button">
                                    <div class="btn-icon icon-pasta"></div>
                                    <span class="link-title">Criar nova pasta</span>
                                </button>

                                <button class="silo-dados-add-btn silo-action-scan" id="btn-silo-scan" type="button">
                                    <div class="btn-icon icon-camera"></div>
                                    <span class="link-title">Escanear documento</span>
                                </button>
                            </div>
                        </div>

                        <button class="silo-dados-btn v1" id="dado-add" type="button" aria-label="Adicionar">
                            <div class="btn-icon icon-plus cor-branco"></div>
                        </button>
                    </div>
                </div>
            </div>

            <div id="uploadPopup" class="upload-popup" hidden>
                <div class="upload-box">
                    <h3>Enviando arquivos</h3>
                    <div id="uploadLista"></div>
                    <p id="uploadResumo" class="progress-text">Preparando...</p>
                    <button id="btnCancelarUpload" class="upload-cancel-btn" type="button">Cancelar upload</button>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/silo.js"></script>
        <script src="../js/silo_pasta.js"></script>
        <script src="../js/silo_mover.js"></script>
        <script src="../js/silo_upload.js"></script>
        <script src="../js/silo_busca.js"></script>
        <script src="../js/silo_uso.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
