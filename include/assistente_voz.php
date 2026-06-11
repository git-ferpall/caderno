<link rel="stylesheet" href="../css/custom/assistente-voz.css">

<div id="assistente-voz" class="assistente-voz" aria-live="polite">
    <button type="button" id="assistente-voz-btn" class="assistente-voz-fab" aria-label="Assistente por voz" title="Assistente por voz">
        <span class="assistente-voz-fab-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="3" width="6" height="11" rx="3"/>
                <path d="M5 11a7 7 0 0 0 14 0"/>
                <path d="M12 18v3"/>
                <path d="M8.5 21h7"/>
                <path class="assistente-voz-wave assistente-voz-wave--1" d="M3.5 11c0-2.2.8-3.5 1.2-4"/>
                <path class="assistente-voz-wave assistente-voz-wave--2" d="M20.5 11c0-2.2-.8-3.5-1.2-4"/>
            </svg>
        </span>
    </button>

    <div id="assistente-voz-panel" class="assistente-voz-panel d-none" role="dialog" aria-labelledby="assistente-voz-titulo">
        <div class="assistente-voz-header">
            <h3 id="assistente-voz-titulo" class="assistente-voz-titulo">Assistente por voz</h3>
            <button type="button" id="assistente-voz-fechar" class="assistente-voz-fechar" aria-label="Fechar">&times;</button>
        </div>

        <p id="assistente-voz-status" class="assistente-voz-status">Toque no botão laranja e permita o microfone quando o navegador solicitar.</p>
        <p id="assistente-voz-transcricao" class="assistente-voz-transcricao d-none"></p>

        <div id="assistente-voz-dialogo" class="assistente-voz-dialogo d-none" role="status">
            <p id="assistente-voz-pergunta" class="assistente-voz-pergunta"></p>
            <p class="assistente-voz-dialogo-hint">Toque em <strong>Gravar</strong> e responda em voz alta.</p>
        </div>

        <div id="assistente-voz-confirmacao" class="assistente-voz-confirmacao d-none">
            <p id="assistente-voz-resumo" class="assistente-voz-resumo"></p>
            <div class="assistente-voz-acoes">
                <button type="button" id="assistente-voz-cancelar" class="assistente-voz-btn assistente-voz-btn--secundario">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3z"/>
                        <path d="M19 11a7 7 0 0 1-14 0"/>
                        <path d="M12 18v3"/>
                        <path d="M8 21h8"/>
                    </svg>
                    <span>Gravar de novo</span>
                </button>
                <button type="button" id="assistente-voz-confirmar" class="assistente-voz-btn assistente-voz-btn--primario">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    <span>Confirmar</span>
                </button>
            </div>
        </div>

        <div class="assistente-voz-controles">
            <button type="button" id="assistente-voz-gravar" class="assistente-voz-gravar" aria-pressed="false">
                <span class="assistente-voz-gravar-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="12" r="6"/>
                    </svg>
                </span>
                <span id="assistente-voz-gravar-texto">Gravar</span>
            </button>
        </div>
    </div>
</div>

<script src="../js/assistente-voz.js"></script>
