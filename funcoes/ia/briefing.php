<?php
declare(strict_types=1);

require_once __DIR__ . '/contexto_usuario.php';
require_once __DIR__ . '/consultas.php';

/** Briefing proativo do agente (web + WhatsApp). */
function iaGerarBriefing(mysqli $mysqli, int $user_id): string
{
    $prop = obterPropriedadeAtiva($mysqli, $user_id);
    if (!$prop) {
        return 'Olá! Vincule uma propriedade ativa para eu consultar seu caderno.';
    }

    $propId = (int) $prop['id'];
    $nome = trim((string) ($prop['nome_razao'] ?? ''));
    $saudacao = (int) date('H') < 12 ? 'Bom dia' : ((int) date('H') < 18 ? 'Boa tarde' : 'Boa noite');

    $contagem = iaConsultaContarPendentes($mysqli, $propId);
    $total = (int) ($contagem['dados']['total'] ?? 0);

    $partes = [$saudacao . '!'];
    if ($nome !== '') {
        $partes[] = 'Aqui é seu agente do Caderno Frutag' . ($nome ? ' — ' . $nome : '') . '.';
    }

    if ($total === 0) {
        $partes[] = 'Seu caderno está em dia, sem pendências.';
    } else {
        $partes[] = "Você tem {$total} apontamento" . ($total > 1 ? 's' : '') . ' pendente' . ($total > 1 ? 's' : '') . '.';
        $amostra = $contagem['dados']['amostra'] ?? [];
        if ($amostra) {
            $linhas = array_map(static fn ($i) => iaFormatarLinhaManejo($i), array_slice($amostra, 0, 2));
            $partes[] = implode('; ', $linhas) . '.';
        }
    }

    $ultima = iaBuscarUltimoApontamento($mysqli, $propId, 'colheita');
    if ($ultima && !empty($ultima['quantidade'])) {
        $partes[] = 'Última colheita: '
            . iaFormatarQuantidade((float) $ultima['quantidade'], (string) ($ultima['unidade'] ?: 'kg'))
            . ' em ' . iaFormatarDataConsulta((string) $ultima['data']) . '.';
    }

    $partes[] = 'Posso registrar manejos, consultar dados ou marcar pendências como feitas.';

    return implode(' ', $partes);
}
