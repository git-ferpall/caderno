<link rel="stylesheet" href="../css/custom/assistente-voz.css">

<div id="assistente-voz" class="assistente-voz" aria-live="polite">
    <button type="button" id="assistente-voz-btn" class="assistente-voz-fab" aria-label="Assistente por voz" title="Assistente por voz">
        <span class="assistente-voz-fab-icon" aria-hidden="true">🎤</span>
    </button>

    <div id="assistente-voz-panel" class="assistente-voz-panel d-none" role="dialog" aria-labelledby="assistente-voz-titulo">
        <div class="assistente-voz-header">
            <h3 id="assistente-voz-titulo" class="assistente-voz-titulo">Assistente por voz</h3>
            <button type="button" id="assistente-voz-fechar" class="assistente-voz-fechar" aria-label="Fechar">&times;</button>
        </div>

        <p id="assistente-voz-status" class="assistente-voz-status">Toque no microfone e fale seu comando.</p>
        <p id="assistente-voz-transcricao" class="assistente-voz-transcricao d-none"></p>

        <div id="assistente-voz-confirmacao" class="assistente-voz-confirmacao d-none">
            <p id="assistente-voz-resumo" class="assistente-voz-resumo"></p>
            <div class="assistente-voz-acoes">
                <button type="button" id="assistente-voz-confirmar" class="main-btn fundo-verde">
                    <span class="main-btn-text">Confirmar</span>
                </button>
                <button type="button" id="assistente-voz-cancelar" class="main-btn fundo-cinza">
                    <span class="main-btn-text">Cancelar</span>
                </button>
            </div>
        </div>

        <div class="assistente-voz-controles">
            <button type="button" id="assistente-voz-gravar" class="assistente-voz-gravar" aria-pressed="false">
                <span class="assistente-voz-gravar-icon" aria-hidden="true">●</span>
                <span id="assistente-voz-gravar-texto">Gravar</span>
            </button>
        </div>
    </div>
</div>

<script src="../js/assistente-voz.js"></script>
