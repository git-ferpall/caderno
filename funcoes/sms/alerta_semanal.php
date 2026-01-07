<?php
declare(strict_types=1);

// Conexão com banco
require_once '/var/www/html/configuracao/configuracao_conexao.php';

// Composer (AWS SDK)
require_once '/var/www/html/vendor/autoload.php';

// Função de envio
require_once __DIR__ . '/enviar_sms.php';

/**
 * Normaliza telefone para padrão E.164 (Brasil)
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
 * Executa o alerta SMS semanal (por propriedade)
 */
function executarAlertaSMS(): void
{
    global $mysqli;

    $hoje = new DateTime('today');
    $domingo = (clone $hoje)->modify('sunday this week');

    $usuarios = $mysqli->query("
        SELECT user_id, nome, telefone
        FROM contato_cliente
        WHERE aceita_sms = 1
          AND telefone IS NOT NULL
          AND telefone != ''
    ");

    while ($u = $usuarios->fetch_assoc()) {

        $telefone = normalizarTelefone($u['telefone']);
        if (!$telefone) {
            error_log('[SMS] Telefone inválido: ' . $u['telefone']);
            continue;
        }

        // Todas as propriedades do usuário
        $stmtProp = $mysqli->prepare("
            SELECT id, nome_razao
            FROM propriedades
            WHERE user_id = ?
        ");
        $stmtProp->bind_param('i', $u['user_id']);
        $stmtProp->execute();
        $propriedades = $stmtProp->get_result();

        while ($p = $propriedades->fetch_assoc()) {

            $stmtApt = $mysqli->prepare("
                SELECT data
                FROM apontamentos
                WHERE propriedade_id = ?
                  AND status = 'pendente'
            ");
            $stmtApt->bind_param('i', $p['id']);
            $stmtApt->execute();
            $apontamentos = $stmtApt->get_result();

            $atrasadas = 0;
            $semana = 0;

            while ($a = $apontamentos->fetch_assoc()) {
                $data = new DateTime($a['data']);

                if ($data < $hoje) {
                    $atrasadas++;
                } elseif ($data <= $domingo) {
                    $semana++;
                }
            }

            // Se não há pendências, não envia SMS
            if ($atrasadas === 0 && $semana === 0) {
                continue;
            }

            // Monta mensagem curta
            $msg = "Frutag | {$p['nome_razao']}: ";

            if ($atrasadas > 0 && $semana > 0) {
                $msg .= "{$atrasadas} atrasadas, {$semana} esta semana.";
            } elseif ($atrasadas > 0) {
                $msg .= "{$atrasadas} tarefas atrasadas.";
            } else {
                $msg .= "{$semana} tarefas esta semana.";
            }

            enviarSMS($telefone, $msg);
        }
    }
}
