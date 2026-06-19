<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1b4332">
    <title>IA Fitossanitária - Caderno de Campo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/custom/ia-fitossanitaria.css?v=7">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
</head>
<body class="page-ia-fitossanitaria">
    <?php require '../include/loading.php' ?>
    <?php include '../include/popups.php' ?>

    <div id="conteudo" class="ia-fs-layout">
        <?php include '../include/menu.php' ?>

        <main id="ia-fitossanitaria" class="sistema ia-fs-main">
            <div class="ia-fs-overlay">
                <div class="ia-fs-wrapper">
                    <header class="ia-fs-hero">
                        <div class="ia-fs-hero-text">
                            <span class="ia-fs-eyebrow">Inteligência de campo</span>
                            <h2>IA Fitossanitária</h2>
                            <p>Score, carência e decisão técnica por área</p>
                        </div>
                        <a href="/home/relatorios" class="ia-fs-voltar">← Relatórios</a>
                    </header>

                    <div class="ia-fs-toolbar ia-fs-glass ia-fs-toolbar-sticky">
                        <label class="ia-fs-field">
                            <span>Área / talhão</span>
                            <select id="ia-fs-area" class="form-select form-text">
                                <option value="">Carregando áreas...</option>
                            </select>
                        </label>
                        <button type="button" id="ia-fs-atualizar" class="ia-fs-btn ia-fs-btn-primary">
                            Atualizar painel
                        </button>
                        <button type="button" id="ia-fs-sync-agrofit" class="ia-fs-btn ia-fs-btn-secondary" title="Sincronizar catálogo local">
                            Sync AGROFIT
                        </button>
                    </div>

                    <div id="ia-fs-overview" class="ia-fs-panel ia-fs-glass ia-fs-overview" hidden>
                        <div class="ia-fs-panel-head">
                            <h3 class="ia-fs-panel-title">Visão geral das áreas</h3>
                            <div class="ia-fs-legend" aria-hidden="true">
                                <span><i class="ia-fs-dot" style="--dot:#2e7d32"></i>Conforme</span>
                                <span><i class="ia-fs-dot" style="--dot:#f9a825"></i>Atenção</span>
                                <span><i class="ia-fs-dot" style="--dot:#c62828"></i>Risco</span>
                                <span><i class="ia-fs-dot" style="--dot:#9e9e9e"></i>Sem dados</span>
                            </div>
                        </div>
                        <p class="ia-fs-swipe-hint" id="ia-fs-swipe-hint" hidden>
                            <span class="ia-fs-swipe-arrows" aria-hidden="true">‹ ›</span>
                            Deslize para ver todas as áreas
                        </p>
                        <div class="ia-fs-area-cards-wrap">
                            <div id="ia-fs-area-cards" class="ia-fs-area-cards"></div>
                        </div>
                    </div>

                    <div id="ia-fs-painel" class="ia-fs-painel" hidden>
                        <div class="ia-fs-score-card ia-fs-glass" id="ia-fs-score-card">
                            <div class="ia-fs-score-badge" id="ia-fs-score-badge">—</div>
                            <div class="ia-fs-score-body">
                                <h3 id="ia-fs-score-label">Selecione uma área</h3>
                                <p id="ia-fs-score-explicacao"></p>
                                <ul id="ia-fs-score-motivos" class="ia-fs-motivos"></ul>
                            </div>
                        </div>

                        <div class="ia-fs-grid">
                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Diagnóstico</h4>
                                <p id="ia-fs-diagnostico" class="ia-fs-text">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Risco fitossanitário</h4>
                                <p id="ia-fs-risco-fit" class="ia-fs-text">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Risco de resíduo</h4>
                                <p id="ia-fs-risco-res" class="ia-fs-text">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Status de carência</h4>
                                <div id="ia-fs-carencias" class="ia-fs-lista">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Ingredientes ativos recentes</h4>
                                <div id="ia-fs-ia" class="ia-fs-tags">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Cultura / produto</h4>
                                <div id="ia-fs-cultura" class="ia-fs-tags">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Clima — aplicação</h4>
                                <div id="ia-fs-clima" class="ia-fs-text">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>AGROFIT / cultura</h4>
                                <div id="ia-fs-agrofit" class="ia-fs-text">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>CSFI</h4>
                                <p id="ia-fs-csfi" class="ia-fs-text ia-fs-muted">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-block-wide ia-fs-glass ia-fs-lote-block">
                                <h4>Lote Frutag · auditoria</h4>
                                <div id="ia-fs-lote" class="ia-fs-lote">Selecione uma área.</div>
                                <div class="ia-fs-lote-actions" id="ia-fs-lote-actions" hidden>
                                    <button type="button" id="ia-fs-pdf-lote" class="ia-fs-btn ia-fs-btn-primary">Gerar PDF do lote</button>
                                    <a id="ia-fs-lote-verificar" class="ia-fs-btn ia-fs-btn-secondary" href="#" target="_blank" rel="noopener">Abrir verificação</a>
                                </div>
                            </section>

                            <section class="ia-fs-block ia-fs-block-wide ia-fs-glass">
                                <h4>Histórico de aplicações</h4>
                                <div id="ia-fs-historico" class="ia-fs-historico">—</div>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Recomendação</h4>
                                <p id="ia-fs-recomendacao" class="ia-fs-text">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-glass">
                                <h4>Ação sugerida</h4>
                                <p id="ia-fs-acao" class="ia-fs-text">—</p>
                            </section>

                            <section class="ia-fs-block ia-fs-block-wide ia-fs-glass">
                                <h4>Validação do agrônomo</h4>
                                <div id="ia-fs-validacao-atual" class="ia-fs-text ia-fs-muted">Nenhuma validação registrada.</div>
                                <form id="ia-fs-validacao-form" class="ia-fs-validacao-form">
                                    <textarea id="ia-fs-validacao-texto" class="form-text form-textarea v2" rows="3" placeholder="Registre parecer técnico, liberação ou orientação..."></textarea>
                                    <button type="submit" class="ia-fs-btn ia-fs-btn-accent">Salvar validação</button>
                                </form>
                            </section>

                            <section class="ia-fs-block ia-fs-block-wide ia-fs-glass ia-fs-chat">
                                <h4>Perguntar para IA</h4>
                                <p class="ia-fs-hint">Fale sua pergunta sobre a área selecionada ou toque numa sugestão</p>
                                <div class="ia-fs-quick-questions" id="ia-fs-quick-questions">
                                    <button type="button" class="ia-fs-quick-btn" data-q="Posso colher hoje?">Posso colher hoje?</button>
                                    <button type="button" class="ia-fs-quick-btn" data-q="Qual a carência ativa?">Carência ativa</button>
                                    <button type="button" class="ia-fs-quick-btn" data-q="Qual o score desta área?">Score da área</button>
                                    <button type="button" class="ia-fs-quick-btn" data-q="O lote está liberado?">Lote liberado?</button>
                                    <button type="button" class="ia-fs-quick-btn" data-q="Posso aplicar defensivo hoje?">Aplicar hoje?</button>
                                    <button type="button" class="ia-fs-quick-btn" data-q="Essa cultura é CSFI?">É CSFI?</button>
                                </div>
                                <div id="ia-fs-chat-log" class="ia-fs-chat-log"></div>
                                <div class="ia-fs-voice-composer">
                                    <p class="ia-fs-voice-status" id="ia-fs-voice-status">Selecione uma área para falar</p>
                                    <button type="button" id="ia-fs-mic" class="ia-fs-mic-btn" disabled aria-pressed="false" aria-label="Gravar pergunta por áudio">
                                        <span class="ia-fs-mic-ring" aria-hidden="true"></span>
                                        <span class="ia-fs-mic-icon" aria-hidden="true">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21h2v-3.08A7 7 0 0 0 19 11h-2z"/></svg>
                                        </span>
                                        <span id="ia-fs-mic-label">Toque para falar</span>
                                    </button>
                                    <details class="ia-fs-chat-text-fallback">
                                        <summary>Ou digite sua pergunta</summary>
                                        <form id="ia-fs-chat-form" class="ia-fs-chat-form">
                                            <input type="text" id="ia-fs-chat-input" class="form-text v2" placeholder="Digite sua pergunta..." autocomplete="off" />
                                            <button type="submit" class="ia-fs-btn ia-fs-btn-primary">Enviar</button>
                                        </form>
                                    </details>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div id="ia-fs-loading" class="ia-fs-panel ia-fs-loading">Carregando...</div>
                    <div id="ia-fs-erro" class="ia-fs-erro" hidden></div>
                </div>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/ia-fitossanitaria.js?v=8"></script>
        <?php include '../include/footer.php' ?>
    </div>
</body>
</html>
