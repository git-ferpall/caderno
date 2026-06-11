<link rel="stylesheet" href="../css/custom/assistente-voz.css">

<div id="assistente-voz" class="assistente-voz" aria-live="polite">
    <div id="assistente-voz-backdrop" class="assistente-voz-backdrop d-none" aria-hidden="true"></div>

    <button type="button" id="assistente-voz-btn" class="assistente-voz-fab" aria-label="Assistente Frutag" title="Assistente Frutag">
        <span class="assistente-voz-fab-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="3" width="6" height="11" rx="3"/>
                <path d="M5 11a7 7 0 0 0 14 0"/>
                <path d="M12 18v3"/>
                <path d="M8.5 21h7"/>
            </svg>
        </span>
    </button>

    <div id="assistente-voz-panel" class="assistente-voz-panel d-none" role="dialog" aria-labelledby="assistente-voz-titulo" aria-modal="true">
        <div class="assistente-voz-sheet-handle" aria-hidden="true"></div>

        <div class="assistente-voz-header">
            <div class="assistente-voz-header-info">
                <span class="assistente-voz-avatar" id="assistente-voz-avatar" aria-hidden="true">🌱</span>
                <div>
                    <h3 id="assistente-voz-titulo" class="assistente-voz-titulo">Assistente Frutag</h3>
                    <p class="assistente-voz-subtitulo">Seu caderno de campo por voz</p>
                </div>
            </div>
            <button type="button" id="assistente-voz-fechar" class="assistente-voz-fechar" aria-label="Fechar">&times;</button>
        </div>

        <div id="assistente-voz-progresso" class="assistente-voz-progresso d-none" aria-hidden="true">
            <div class="assistente-voz-progresso-bar">
                <div id="assistente-voz-progresso-fill" class="assistente-voz-progresso-fill"></div>
            </div>
            <span id="assistente-voz-progresso-label" class="assistente-voz-progresso-label"></span>
        </div>

        <div id="assistente-voz-chat" class="assistente-voz-chat" role="log" aria-relevant="additions"></div>

        <div id="assistente-voz-digitando" class="assistente-voz-digitando d-none" aria-hidden="true">
            <span class="assistente-voz-avatar assistente-voz-avatar--mini">🌱</span>
            <span class="assistente-voz-digitando-bolas"><span></span><span></span><span></span></span>
        </div>

        <p id="assistente-voz-hint" class="assistente-voz-hint">Toque no botão laranja e fale seu comando</p>
        <p id="assistente-voz-status" class="assistente-voz-status assistente-voz-status--sr">Assistente por voz</p>

        <div id="assistente-voz-confirmacao" class="assistente-voz-confirmacao d-none">
            <div class="assistente-voz-resumo-card">
                <p class="assistente-voz-resumo-titulo">Resumo do manejo</p>
                <p id="assistente-voz-resumo" class="assistente-voz-resumo"></p>
            </div>
            <div class="assistente-voz-acoes">
                <button type="button" id="assistente-voz-cancelar" class="assistente-voz-btn assistente-voz-btn--secundario">
                    <span>Gravar de novo</span>
                </button>
                <button type="button" id="assistente-voz-confirmar" class="assistente-voz-btn assistente-voz-btn--primario">
                    <span>Confirmar</span>
                </button>
            </div>
        </div>

        <div class="assistente-voz-controles">
            <div id="assistente-voz-ondas" class="assistente-voz-ondas d-none" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span>
            </div>
            <button type="button" id="assistente-voz-gravar" class="assistente-voz-gravar" aria-pressed="false">
                <span class="assistente-voz-gravar-ring" aria-hidden="true"></span>
                <span class="assistente-voz-gravar-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3z"/>
                    </svg>
                </span>
                <span id="assistente-voz-gravar-texto">Toque para falar</span>
            </button>
        </div>
    </div>
</div>

<script src="../js/assistente-voz.js"></script>
