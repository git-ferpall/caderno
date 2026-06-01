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
</head>
<body>
    <?php require '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?>

    <div id="conteudo" class="home-layout">
        <?php include '../include/menu.php' ?>

        <main id="home" class="sistema fundo-img">
            <div class="container home-page-shell">
            <div class="home-hero">
                <div class="home-hero-panel">
                    <div class="home-hero-top">
                        <div class="home-property">
                            <div class="home-property-icon" aria-hidden="true">🏡</div>
                            <div class="home-property-body">
                                <?php if (!empty($propriedades)) :
                                    $propriedade = $propriedades[0]; ?>
                                    <h2 class="home-property-name"><?= htmlspecialchars($propriedade['nome_razao']) ?></h2>
                                    <button class="home-property-edit" type="button" onclick="altProp()">Alterar propriedade</button>
                                <?php else : ?>
                                    <h2 class="home-property-name">Nenhuma propriedade</h2>
                                    <button class="home-property-edit" type="button" onclick="altProp()">Cadastrar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="./timeline" class="home-timeline-btn">
                            <span class="home-timeline-icon" aria-hidden="true">📅</span>
                            Linha do tempo
                        </a>
                    </div>

                    <div class="home-actions">
                        <div class="home-actions-primary">
                            <a href="./apontamento" class="home-action-btn home-action-main home-action-green">
                                <span class="home-action-icon"><div class="btn-icon icon-plus cor-branco"></div></span>
                                <span class="home-action-text">Novo apontamento</span>
                            </a>
                            <a href="./silo" class="home-action-btn home-action-main home-action-blue">
                                <span class="home-action-icon"><div class="btn-icon icon-silo cor-branco"></div></span>
                                <span class="home-action-text">Silo de dados</span>
                            </a>
                        </div>
                        <div class="home-actions-secondary">
                            <a href="./produtos" class="home-action-btn home-action-sm">
                                <div class="btn-icon icon-fruit"></div>
                                <span>Produtos</span>
                            </a>
                            <a href="./areas" class="home-action-btn home-action-sm">
                                <div class="btn-icon icon-plant"></div>
                                <span>Áreas</span>
                            </a>
                            <a href="./relatorios" class="home-action-btn home-action-sm">
                                <div class="btn-icon icon-pen"></div>
                                <span>Relatórios</span>
                            </a>
                        </div>
                    </div>

                    <section class="home-dashboard" id="home-dashboard" aria-label="Resumo da propriedade">
                        <div class="home-dashboard-grid" id="home-dashboard-grid">
                            <div class="home-stat home-stat-loading">
                                <span class="home-stat-value">…</span>
                                <span class="home-stat-label">Carregando</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="apontamento home-manejos-wrap">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="apontamento-collapse apontamento-fazer manejos-panel active">
                            <div class="apontamento-header" id="manejo-fazer">
                                <div class="apontamento-title">
                                    <span class="apontamento-count cor-laranja">0</span>
                                    <h2 class="apontamento-title-text">Manejo a fazer</h2>
                                </div>
                                <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                            </div>

                            <div class="main-tabela">
                                <div class="manejos-table-wrap">
                                    <table class="apontamento-tabela">
                                        <thead>
                                            <tr>
                                                <th id="apt-data">Data</th>
                                                <th id="apt-nome">Apontamento</th>
                                                <th id="apt-area">Área</th>
                                                <th id="apt-cult">Cultivo</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="manejos-pagination" data-status="pendente">
                                    <button type="button" class="manejos-page-btn" data-status="pendente" data-dir="-1" aria-label="Página anterior">&lt;</button>
                                    <span class="manejos-page-text" data-status="pendente">Página 1</span>
                                    <button type="button" class="manejos-page-btn" data-status="pendente" data-dir="1" aria-label="Próxima página">&gt;</button>
                                </div>

                                <p class="nenhum-apontamento">Nenhum apontamento pendente</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-6">
                        <div class="apontamento-collapse apontamento-concluido manejos-panel active">
                            <div class="apontamento-header" id="manejo-concluido">
                                <div class="apontamento-title">
                                    <span class="apontamento-count cor-azul">0</span>
                                    <h2 class="apontamento-title-text">Manejo concluído</h2>
                                </div>
                                <div class="btn-icon icon-angle apontamento-icon cor-branco"></div>
                            </div>
                        
                            <div class="main-tabela">
                                <div class="manejos-table-wrap">
                                    <table class="apontamento-tabela">
                                        <thead>
                                            <tr>
                                                <th id="apt-data">Data</th>
                                                <th id="apt-conclusao">Conclusão</th>
                                                <th id="apt-nome">Apontamento</th>
                                                <th id="apt-area">Área</th>
                                                <th id="apt-cult">Cultivo</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="manejos-pagination" data-status="concluido">
                                    <button type="button" class="manejos-page-btn" data-status="concluido" data-dir="-1" aria-label="Página anterior">&lt;</button>
                                    <span class="manejos-page-text" data-status="concluido">Página 1</span>
                                    <button type="button" class="manejos-page-btn" data-status="concluido" data-dir="1" aria-label="Próxima página">&gt;</button>
                                </div>

                                <p class="nenhum-apontamento">Nenhum apontamento concluído</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/home_dashboard.js"></script>
        <script src="../js/home_manejos.js"></script>
        <script src="../js/home_manejos_popup.js"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>