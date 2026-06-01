<?php

require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Manejos - Caderno de Campo</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    #conteudo.rel-manejos-layout {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .rel-manejos-page {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-image: url("../img/bg-sistema.jpg");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }

    .rel-manejos-overlay {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 0 20px 32px;
    }

    .rel-manejos-page .page-title {
        padding: 88px 10px 28px;
        margin-bottom: 8px;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.42) 0%, rgba(0, 0, 0, 0.08) 70%, transparent 100%);
    }

    .rel-manejos-page .main-title {
        padding-top: 0;
        margin: 0;
        font-size: 2rem;
        letter-spacing: 0.02em;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
    }

    .rel-manejos-wrapper {
        max-width: 760px;
        margin: 0 auto;
    }

    .rel-manejos-voltar {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 18px;
        opacity: 0.92;
        transition: opacity 0.2s ease;
    }

    .rel-manejos-voltar:hover {
        opacity: 1;
    }

    .rel-manejos-card {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.14);
        padding: 32px 32px 28px;
        backdrop-filter: blur(6px);
    }

    .rel-manejos-card-header {
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e8ecef;
    }

    .rel-manejos-card-header h3 {
        margin: 0 0 8px;
        font-size: 1.35rem;
        color: #1b4332;
    }

    .rel-manejos-card-header p {
        margin: 0;
        color: #5f6b7a;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .rel-manejos-form .form-campo {
        margin-bottom: 20px;
    }

    .rel-manejos-form label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.88rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #2d3748;
        text-transform: uppercase;
    }

    .rel-manejos-form .form-text,
    .rel-manejos-form .form-select {
        width: 100%;
        min-height: 48px;
        padding: 12px 14px;
        border: 1px solid #d7dde5;
        border-radius: 12px;
        background: #f9fafb;
        font-size: 15px;
        color: #1f2937;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .rel-manejos-form .form-text:focus,
    .rel-manejos-form .form-select:focus {
        outline: none;
        border-color: #2e7d32;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12);
    }

    .rel-manejos-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .rel-manejos-submit {
        margin-top: 8px;
        padding-top: 8px;
    }

    .rel-manejos-submit .main-btn {
        width: 100%;
        min-height: 52px;
        border: none;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.02em;
        box-shadow: 0 10px 24px rgba(237, 108, 2, 0.28);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .rel-manejos-submit .main-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(237, 108, 2, 0.34);
    }

    /* Select2 */
    .rel-manejos-form .select2-container--default .select2-selection--multiple,
    .rel-manejos-form .select2-container--default .select2-selection--single {
        min-height: 48px;
        border: 1px solid #d7dde5;
        border-radius: 12px;
        background: #f9fafb;
        padding: 4px 8px;
    }

    .rel-manejos-form .select2-container--default.select2-container--focus .select2-selection--multiple,
    .rel-manejos-form .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #2e7d32;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12);
    }

    .rel-manejos-form .select2-container--default .select2-selection__choice {
        background: #2e7d32;
        border: none;
        color: #fff;
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 13px;
    }

    .rel-manejos-form .select2-container--default .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.85);
        margin-right: 6px;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #ddd;
        border-top: 5px solid #4caf50;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: auto;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    #pdf-loading {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.88);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        font-family: sans-serif;
    }

    .rel-manejos-layout #footer {
        margin-top: auto;
        flex-shrink: 0;
        background: transparent;
        padding: 12px 10px 18px;
        width: 100%;
    }

    .rel-manejos-layout #footer .ferpall {
        background: rgba(0, 0, 0, 0.82);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    }

    @media (max-width: 640px) {
        .rel-manejos-page .page-title {
            padding-top: 76px;
        }

        .rel-manejos-page .main-title {
            font-size: 1.6rem;
        }

        .rel-manejos-card {
            padding: 24px 20px 20px;
        }

        .rel-manejos-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>
    <?php include '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="pdf-loading">
        <div style="text-align:center">
            <div class="spinner"></div>
            <p style="margin-top:10px;font-weight:bold;color:#2e7d32">
                Gerando relatório, aguarde...
            </p>
        </div>
    </div>

    <div id="conteudo" class="rel-manejos-layout">
        <?php include '../include/menu.php' ?>

        <?php
        date_default_timezone_set("America/Sao_Paulo");

        $cultivos = [];
        $areas = [];
        $manejos = [];

        $dt_ini = date("Y-m-01");
        $dt_fin = date("Y-m-t");
        ?>

        <main id="relatorios" class="sistema rel-manejos-page">
            <div class="rel-manejos-overlay">
                <div class="page-title">
                    <h2 class="main-title cor-branco">Relatórios</h2>
                </div>

                <div class="rel-manejos-wrapper">
                    <a href="relatorios" class="rel-manejos-voltar">← Voltar para central de relatórios</a>

                    <div class="rel-manejos-card">
                        <div class="rel-manejos-card-header">
                            <h3>Relatório de Manejos</h3>
                            <p>Selecione os filtros abaixo para gerar um PDF com aplicações, defensivos e operações realizadas no período.</p>
                        </div>

                        <form action="relatorios" class="main-form rel-manejos-form" id="rel-form">
                            <div class="form-campo">
                                <label for="pf-propriedades">Propriedades</label>
                                <select name="pfpropriedades[]" id="pf-propriedades" class="form-select form-text f1" multiple required>
                                    <option value="">Carregando...</option>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label for="pf-cult">Cultivos</label>
                                <select name="pfcult" id="pf-cult" class="form-select form-text f1" required>
                                    <option value="" selected>Todos os cultivos</option>
                                    <?php
                                    if (!empty($cultivos)) {
                                        foreach ($cultivos as $cultivo) {
                                            echo '<option value="' . strtolower($cultivo) . '">' . htmlspecialchars($cultivo) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label for="pf-area">Áreas</label>
                                <select name="pfarea" id="pf-area" class="form-select form-text f1" required>
                                    <option value="" selected>Todas as áreas</option>
                                    <?php
                                    if (!empty($areas)) {
                                        foreach ($areas as $area) {
                                            echo '<option value="' . strtolower($area) . '">' . htmlspecialchars($area) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-campo">
                                <label for="pf-mane">Tipos de manejo</label>
                                <select name="pfmane" id="pf-mane" class="form-select form-text f1" required>
                                    <option value="" selected>Todos os tipos de manejo</option>
                                    <?php
                                    if (!empty($manejos)) {
                                        foreach ($manejos as $manejo) {
                                            echo '<option value="' . strtolower($manejo) . '">' . htmlspecialchars($manejo) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="rel-manejos-grid-2">
                                <div class="form-campo">
                                    <label for="pf-ini">Data inicial</label>
                                    <input class="form-text only-num" type="date" name="pfini" id="pf-ini" value="<?php echo $dt_ini ?>" required>
                                </div>

                                <div class="form-campo">
                                    <label for="pf-fin">Data final</label>
                                    <input class="form-text only-num" type="date" name="pffin" id="pf-fin" value="<?php echo $dt_fin ?>" required>
                                </div>
                            </div>

                            <div class="form-submit rel-manejos-submit">
                                <button class="main-btn fundo-laranja" id="form-pdf-relatorio" type="button">
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
        <script src="../js/relatorios.js"></script>

        <script>
        document.addEventListener("DOMContentLoaded", () => {
            $('#pf-propriedades').select2({
                placeholder: "Selecione uma ou mais propriedades",
                width: '100%',
                language: "pt-BR"
            });
        });
        </script>

        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
