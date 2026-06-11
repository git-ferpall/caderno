<?php
declare(strict_types=1);

require_once __DIR__ . '/ia_helpers.php';

/** Converte clique nos cards da UI em intent do pipeline. */
function iaMapAcaoRapida(array $acao): array
{
    $tipo = (string) ($acao['tipo'] ?? '');
    $id = (int) ($acao['apontamento_id'] ?? 0);

    return match ($tipo) {
        'concluir' => [
            'acao' => 'concluir_apontamento',
            'apontamento_id' => $id,
            'confianca' => 1.0,
            'mensagem' => 'Vou concluir esse pendente.',
        ],
        'detalhar' => [
            'acao' => 'consultar',
            'consulta' => 'detalhar_pendente',
            'apontamento_id' => $id,
            'confianca' => 1.0,
        ],
        'editar_obs' => [
            'acao' => 'editar_apontamento',
            'apontamento_id' => $id,
            'observacoes' => trim((string) ($acao['observacoes'] ?? '')),
            'confianca' => 1.0,
        ],
        default => ['acao' => 'desconhecido', 'mensagem' => 'Ação não reconhecida.'],
    };
}
