<?php
require_once '/var/www/html/configuracao/configuracao_conexao.php';
require_once '/var/www/html/vendor/autoload.php';
require_once __DIR__ . '/enviar_sms.php';

function executarAlertaSMS()
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
            continue;
        }

        $props = $mysqli->prepare("
            SELECT id, nome_razao
            FROM propriedades
            WHERE user_id = ?
        ");
        $props->bind_param('i', $u['user_id']);
        $props->execute();
        $propriedades = $props->get_result();

        while ($p = $propriedades->fetch_assoc()) {

            $aps = $mysqli->prepare("
                SELECT data
                FROM apontamentos
                WHERE propriedade_id = ?
                  AND status = 'pendente'
            ");
            $aps->bind_param('i', $p['id']);
            $aps->execute();
            $res = $aps->get_result();

            $atrasadas = 0;
            $semana = 0;

            while ($a = $res->fetch_assoc()) {
                $data = new DateTime($a['data']);

                if ($data < $hoje) {
                    $atrasadas++;
                } elseif ($data <= $domingo) {
                    $semana++;
                }
            }

            if ($atrasadas === 0 && $semana === 0) {
                continue;
            }

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
