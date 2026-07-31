<?php
declare(strict_types=1);

// Conexão com banco
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';

// Composer (AWS SDK)
require_once __DIR__ . '/../../vendor/autoload.php';

// Função de envio
require_once __DIR__ . '/enviar_sms.php';
require_once __DIR__ . '/../alertas/resumo_semanal.php';

/**
 * Normaliza telefone para E.164 (Brasil)
 */
function normalizarTelefone(string $tel): ?string
{
    $num = preg_replace('/\D/', '', $tel);

    // Brasil: DDD + 9 dígitos
    if (strlen($num) === 11) {
        return '+55' . $num;
    }

    // Já com DDI
    if (strlen($num) === 13 && str_starts_with($num, '55')) {
        return '+' . $num;
    }

    return null;
}

/**
 * Executa o alerta SMS semanal (1 SMS por destinatário: dono + funcionários)
 */
function executarAlertaSMS(): void
{
    global $mysqli;

    foreach (alertaDestinatariosSms($mysqli) as $dest) {
        $telefone = normalizarTelefone($dest['telefone']);
        if (!$telefone) {
            error_log('[SMS] Telefone inválido: ' . $dest['telefone']);
            continue;
        }

        $resumo = alertaResumoPropriedades(
            $mysqli,
            $dest['conta_id'],
            $dest['funcionario_id']
        );

        if ($resumo === []) {
            continue;
        }

        $linhas = [];
        foreach ($resumo as $item) {
            $linhas[] =
                "🏡 {$item['nome_razao']}\n" .
                "🔴 Atrasadas: {$item['atrasadas']} | 🟡 Pendentes: {$item['pendentes']}";
        }

        $msg =
            "📒 Caderno de Campo\n\n" .
            "📅 Tarefas para essa semana\n\n" .
            implode("\n\n", $linhas);

        $msg = mb_strimwidth($msg, 0, 320, '...');

        enviarSMS($telefone, $msg);
    }
}
