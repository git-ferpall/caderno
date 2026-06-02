<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Irrigação - Caderno de Campo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body class="page-relatorios">
    <?php include '../include/popups.php' ?>

    <div id="pdf-loading">
        <div style="text-align:center">
            <div class="rel-form-spinner"></div>
            <p style="margin-top:10px;font-weight:bold;color:#2e7d32">Gerando relatório, aguarde...</p>
        </div>
    </div>

    <div id="conteudo" class="rel-form-layout">
        <?php include '../include/menu.php' ?>

        <main class="sistema rel-form-page">
            <div class="rel-form-overlay">
                <div class="rel-form-wrapper">
                    <a href="relatorios" class="rel-form-voltar">← Voltar para central de relatórios</a>

                    <div class="rel-form-card">
                        <div class="rel-form-card-header">
                            <h3>Relatório de Irrigação</h3>
                        </div>

                        <form id="rel-form" class="main-form rel-form-form">
                            <div class="form-campo">
                                <label for="ri-propriedade">Propriedade</label>
                                <select id="ri-propriedade" class="form-select form-text" required>
                                    <option value="">Carregando...</option>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label>Áreas (selecione uma ou mais)</label>
                                <div id="ri-areas" class="rel-form-areas-box">
                                    <span style="color:#777;">Selecione uma propriedade para carregar as áreas.</span>
                                </div>
                            </div>

                            <div class="rel-form-grid-2">
                                <div class="form-campo">
                                    <label for="ri-ini">Data inicial</label>
                                    <input type="date" id="ri-ini" class="form-text" required>
                                </div>
                                <div class="form-campo">
                                    <label for="ri-fin">Data final</label>
                                    <input type="date" id="ri-fin" class="form-text" required>
                                </div>
                            </div>

                            <div class="form-submit rel-form-submit">
                                <button type="button" class="main-btn fundo-laranja" id="ri-gerar-pdf">
                                    <span class="main-btn-text">Gerar PDF</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/relatorio_irrigacao.js"></script>

        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
