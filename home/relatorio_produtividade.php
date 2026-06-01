<?php
require_once __DIR__ . '/../configuracao/protect.php';

date_default_timezone_set("America/Sao_Paulo");
$dt_ini = date("Y-m-01");
$dt_fin = date("Y-m-t");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Safra - Caderno de Campo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <?php include '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="loading-overlay">
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
                            <h3>Relatório de Safra</h3>
                        </div>

                        <form
                            id="rel-form"
                            action="../funcoes/relatorios/relatorio_safra_pdf.php"
                            method="POST"
                            target="_blank"
                            class="main-form rel-form-form"
                        >
                            <div class="form-campo">
                                <label for="pf-propriedade">Propriedade</label>
                                <select name="propriedade" id="pf-propriedade" class="form-select form-text" required>
                                    <option value="" disabled selected>Selecione a propriedade</option>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label for="pf-area">Área</label>
                                <select name="area" id="pf-area" class="form-select form-text" required>
                                    <option value="" disabled selected>Selecione a área</option>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label for="pf-produto">Produto</label>
                                <select name="produto" id="pf-produto" class="form-select form-text" required>
                                    <option value="" disabled selected>Selecione um produto</option>
                                </select>
                            </div>

                            <div class="rel-form-grid-2">
                                <div class="form-campo">
                                    <label for="data-ini">Data inicial</label>
                                    <input type="date" name="data_ini" id="data-ini" value="<?= $dt_ini ?>" class="form-text" required>
                                </div>
                                <div class="form-campo">
                                    <label for="data-fim">Data final</label>
                                    <input type="date" name="data_fim" id="data-fim" value="<?= $dt_fin ?>" class="form-text" required>
                                </div>
                            </div>

                            <div class="form-submit rel-form-submit">
                                <button id="form-pdf-relatorio" class="main-btn fundo-laranja" type="button">
                                    <span class="main-btn-text">Gerar PDF</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="../js/relatorio_produtividade.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            $('#pf-propriedade').select2({
                placeholder: "Selecione uma propriedade",
                width: "100%",
                language: "pt-BR"
            });
        });
        </script>

        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
