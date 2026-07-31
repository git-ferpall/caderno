<?php
/**
 * Relatório semanal de apontamentos
 * Responsável apenas pela geração e envio do e-mail
 */
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../alertas/resumo_semanal.php';
require_once __DIR__ . '/../conta/helpers.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;




/* =========================================================
   CONFIGURAÇÕES
========================================================= */
define('EMAIL_FROM', 'naoresponder@frutag.com.br');
define('EMAIL_FROM_NOME', 'Frutag');

/* =========================================================
   FUNÇÕES AUXILIARES
========================================================= */

function gerarGrafico($atrasadas, $semana)
{
    $config = [
        "type" => "doughnut",
        "data" => [
            "labels" => ["Atrasadas", "Planejadas na Semana"],
            "datasets" => [[
                "data" => [count($atrasadas), count($semana)],
                "backgroundColor" => ["#dc3545", "#ffc107"]
            ]]
        ],
        "options" => [
            "plugins" => [
                "legend" => ["position" => "bottom"]
            ]
        ]
    ];

    return "https://quickchart.io/chart?c=" . urlencode(json_encode($config));
}

function enviarEmail($para, $nome, $html)
{
    $mail = new PHPMailer(true);

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'mail.frutag.com.br';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'naoresponder@frutag.com.br';
        $mail->Password = 'Fruta20ferpall2020';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;

        $mail->setFrom('naoresponder@frutag.com.br', 'Frutag');
        $mail->addAddress($para, $nome);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '📋 Relatório semanal de apontamentos';
        $mail->Body    = $html;

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Erro ao enviar email para {$para}: {$mail->ErrorInfo}");
        return false;
    }
}

/* =========================================================
   EXECUÇÃO PRINCIPAL
========================================================= */

function enviarRelatorioSemanal()
{
    global $mysqli;

    $hoje = new DateTime('today');
    $domingo = (clone $hoje)->modify('sunday this week');

    foreach (alertaDestinatariosEmail($mysqli) as $dest) {
        $filtroProp = '';
        if ($dest['funcionario_id']) {
            $filtroProp = contaFuncionarioSqlFiltroPropriedades($mysqli, $dest['funcionario_id'], 'id');
        }

        $stmt = $mysqli->prepare("
            SELECT id, nome_razao
            FROM propriedades
            WHERE user_id = ?{$filtroProp}
            ORDER BY nome_razao
        ");
        $stmt->bind_param('i', $dest['conta_id']);
        $stmt->execute();
        $propriedades = $stmt->get_result();
        $stmt->close();

        if ($propriedades->num_rows === 0) {
            continue;
        }

        $html = "<h2>📋 Relatório semanal de apontamentos</h2>";
        $html .= "<p>Olá <strong>{$dest['nome']}</strong>,</p>";
        $html .= "<p>Confira abaixo seus apontamentos pendentes por propriedade:</p>";

        $temConteudo = false;

        while ($p = $propriedades->fetch_assoc()) {
            $stmt2 = $mysqli->prepare("
                SELECT tipo, data, observacoes
                FROM apontamentos
                WHERE propriedade_id = ?
                  AND status = 'pendente'
                ORDER BY data
            ");
            $propId = (int)$p['id'];
            $stmt2->bind_param('i', $propId);
            $stmt2->execute();
            $aps = $stmt2->get_result();
            $stmt2->close();

            $atrasadas = [];
            $semana = [];

            while ($a = $aps->fetch_assoc()) {
                $dataAp = new DateTime($a['data']);

                if ($dataAp < $hoje) {
                    $atrasadas[] = $a;
                } elseif ($dataAp >= $hoje && $dataAp <= $domingo) {
                    $semana[] = $a;
                }
            }

            if (!$atrasadas && !$semana) {
                continue;
            }

            $temConteudo = true;

            $html .= "<hr>";
            $html .= "<h3>🏡 {$p['nome_razao']}</h3>";
            $html .= "<ul>
                        <li>🔴 <strong>" . count($atrasadas) . "</strong> atrasadas</li>
                        <li>🟡 <strong>" . count($semana) . "</strong> planejadas para esta semana</li>
                      </ul>";

            $html .= "<img src='" . gerarGrafico($atrasadas, $semana) . "' style='max-width:360px'>";

            if ($atrasadas) {
                $html .= "<h4>🔴 Atrasadas</h4><ul>";
                foreach ($atrasadas as $a) {
                    $html .= "<li>
                        <strong>{$a['tipo']}</strong><br>
                        📅 " . date('d/m/Y', strtotime($a['data'])) . "<br>
                        {$a['observacoes']}
                    </li>";
                }
                $html .= "</ul>";
            }

            if ($semana) {
                $html .= "<h4>🟡 Planejadas para esta semana</h4><ul>";
                foreach ($semana as $a) {
                    $html .= "<li>
                        <strong>{$a['tipo']}</strong><br>
                        📅 " . date('d/m/Y', strtotime($a['data'])) . "<br>
                        {$a['observacoes']}
                    </li>";
                }
                $html .= "</ul>";
            }
        }

        if (!$temConteudo) {
            continue;
        }

        $html .= "<p style='font-size:12px;color:#666'>
                    Você está recebendo este e-mail porque autorizou comunicações por e-mail.
                  </p>";

        enviarEmail($dest['email'], $dest['nome'], $html);
    }
}
