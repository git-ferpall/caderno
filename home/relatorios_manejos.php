<?php
require_once __DIR__ . '/../configuracao/protect.php';

date_default_timezone_set("America/Sao_Paulo");

$cultivos = [];
$areas = [];
$manejos = [];
$dt_ini = date("Y-m-01");
$dt_fin = date("Y-m-t");
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
</head>
<body class="page-relatorios">
    <?php include '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="pdf-loading">
        <div class="pdf-loading-box">
            <div class="rel-form-spinner"></div>
            <p class="pdf-loading-title">Gerando relatório, aguarde...</p>
            <p class="pdf-loading-pages" id="pdf-loading-pages">Calculando páginas...</p>
            <div class="pdf-progress-track" aria-hidden="true">
                <div class="pdf-progress-bar" id="pdf-progress-bar"></div>
            </div>
            <p class="pdf-loading-pct" id="pdf-loading-pct">0%</p>
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
                            <div class="rel-form-card-badge" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div>
                                <h3>Relatório de Manejos</h3>
                                <p class="rel-form-card-sub">Filtre os registros e exporte um PDF completo</p>
                            </div>
                        </div>

                        <form action="relatorios" class="main-form rel-form-form" id="rel-form">
                            <div class="rel-form-section">
                                <span class="rel-form-section-title">Filtros</span>

                                <div class="rel-form-grid-filters">
                                    <div class="form-campo form-campo--full">
                                        <label for="pf-propriedades">Propriedades</label>
                                        <select name="pfpropriedades[]" id="pf-propriedades" class="form-select form-text f1" multiple required>
                                            <option value="">Carregando...</option>
                                        </select>
                                    </div>

                                    <div class="form-campo">
                                        <label for="pf-cult">Cultivos</label>
                                        <select name="pfcult" id="pf-cult" class="form-select form-text f1" required>
                                            <option value="" selected>Todos os cultivos</option>
                                            <?php foreach ($cultivos as $cultivo): ?>
                                                <option value="<?= strtolower($cultivo) ?>"><?= htmlspecialchars($cultivo) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-campo">
                                        <label for="pf-area">Áreas</label>
                                        <select name="pfarea" id="pf-area" class="form-select form-text f1" required>
                                            <option value="" selected>Todas as áreas</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= strtolower($area) ?>"><?= htmlspecialchars($area) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-campo">
                                        <label for="pf-mane">Tipos de manejo</label>
                                        <select name="pfmane" id="pf-mane" class="form-select form-text f1" required>
                                            <option value="" selected>Todos os tipos</option>
                                            <?php foreach ($manejos as $manejo): ?>
                                                <option value="<?= strtolower($manejo) ?>"><?= htmlspecialchars($manejo) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="rel-form-section">
                                <span class="rel-form-section-title">Período</span>

                                <div class="rel-form-grid-periodo">
                                    <div class="form-campo">
                                        <label for="pf-ini">Data inicial</label>
                                        <input class="form-text only-num" type="date" name="pfini" id="pf-ini" value="<?= $dt_ini ?>" required>
                                    </div>
                                    <div class="form-campo">
                                        <label for="pf-fin">Data final</label>
                                        <input class="form-text only-num" type="date" name="pffin" id="pf-fin" value="<?= $dt_fin ?>" required>
                                    </div>
                                    <div class="form-campo">
                                        <span class="rel-form-campo-label">Resumo por área</span>
                                        <label class="rel-form-field-toggle" for="pf-resumo-areas" title="Agrega manejos por área e tipo, separando concluídos e pendentes">
                                            <span class="rel-form-field-toggle-label">Incluir no PDF</span>
                                            <span class="rel-form-option-switch" aria-hidden="true"></span>
                                            <input type="checkbox" name="pfresumoareas" id="pf-resumo-areas" value="1" class="rel-form-option-input">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-submit rel-form-submit">
                                <button class="main-btn fundo-laranja rel-form-btn-pdf" id="form-pdf-relatorio" type="button">
                                    <svg class="rel-form-btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="12" y1="18" x2="12" y2="12"/>
                                        <polyline points="9 15 12 18 15 15"/>
                                    </svg>
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
