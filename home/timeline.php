<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linha do tempo - Caderno de Campo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body>
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo" class="home-layout">
        <?php include '../include/menu.php' ?>

        <main id="timeline" class="sistema fundo-img">
            <div class="timeline-page">
                <div class="timeline-header">
                    <h2>Linha do tempo</h2>
                    <p>Histórico de apontamentos e arquivos da propriedade</p>
                </div>

                <form id="timeline-filters" class="timeline-filters">
                    <label>
                        De
                        <input type="date" id="tl-data-ini" name="data_ini">
                    </label>
                    <label>
                        Até
                        <input type="date" id="tl-data-fim" name="data_fim">
                    </label>
                    <label>
                        Status
                        <select id="tl-status" name="status">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="concluido">Concluído</option>
                        </select>
                    </label>
                    <button type="submit">Filtrar</button>
                </form>

                <div id="timeline-feed" class="timeline-feed">
                    <div class="timeline-loading">Carregando...</div>
                </div>

                <div class="timeline-pagination">
                    <button type="button" id="timeline-prev" disabled>&lt; Anterior</button>
                    <span id="timeline-page-text">Página 1</span>
                    <button type="button" id="timeline-next" disabled>Próxima &gt;</button>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/home_manejos_popup.js"></script>
        <script src="../js/home_timeline.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
