<?php
/**
 * Relatório semanal de apontamentos
 * Responsável apenas pela geração e envio do e-mail
 */
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/secrets_loader.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../alertas/resumo_semanal.php';
require_once __DIR__ . '/../conta/helpers.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('EMAIL_FROM', 'naoresponder@frutag.com.br');
define('EMAIL_FROM_NOME', 'Frutag');
define('RELATORIO_APP_URL', 'https://caderno.frutag.com.br/home/');

function relatorioEmailEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function gerarGrafico(array $atrasadas, array $semana): string
{
    $config = [
        'type' => 'doughnut',
        'data' => [
            'labels' => ['Atrasadas', 'Planejadas na semana'],
            'datasets' => [[
                'data' => [count($atrasadas), count($semana)],
                'backgroundColor' => ['#c0392b', '#d97706'],
                'borderWidth' => 0,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['font' => ['size' => 11]],
                ],
            ],
        ],
    ];

    return 'https://quickchart.io/chart?w=320&h=220&c=' . urlencode(json_encode($config));
}

function relatorioEmailTarefasHtml(array $tarefas, string $titulo, string $corTitulo): string
{
    if ($tarefas === []) {
        return '';
    }

    $html = '<p style="margin:20px 0 8px;font-size:13px;font-weight:700;color:' . $corTitulo . ';text-transform:uppercase;letter-spacing:0.04em;">'
        . relatorioEmailEsc($titulo) . '</p>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';

    foreach ($tarefas as $tarefa) {
        $data = date('d/m/Y', strtotime($tarefa['data']));
        $tipo = relatorioEmailEsc((string)$tarefa['tipo']);
        $obs = trim((string)$tarefa['observacoes']);
        $obsHtml = $obs !== ''
            ? '<div style="margin-top:4px;font-size:13px;color:#5f6b66;line-height:1.45;">' . relatorioEmailEsc($obs) . '</div>'
            : '';

        $html .= '<tr>'
            . '<td style="padding:10px 0;border-bottom:1px solid #e8ecea;vertical-align:top;width:88px;font-size:13px;color:#5f6b66;">' . $data . '</td>'
            . '<td style="padding:10px 0 10px 12px;border-bottom:1px solid #e8ecea;vertical-align:top;">'
            . '<div style="font-size:14px;font-weight:600;color:#1f2d2a;">' . $tipo . '</div>'
            . $obsHtml
            . '</td></tr>';
    }

    $html .= '</table>';
    return $html;
}

function relatorioEmailPropriedadeHtml(
    string $nomePropriedade,
    array $atrasadas,
    array $semana
): string {
    $chartUrl = relatorioEmailEsc(gerarGrafico($atrasadas, $semana));
    $nome = relatorioEmailEsc($nomePropriedade);
    $nAtrasadas = count($atrasadas);
    $nSemana = count($semana);

    return '
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;border:1px solid #e2e8e6;border-radius:10px;border-collapse:separate;background:#ffffff;">
        <tr>
            <td style="padding:18px 20px;background:#f7faf9;border-bottom:1px solid #e2e8e6;">
                <div style="font-size:16px;font-weight:700;color:#1f2d2a;">' . $nome . '</div>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top;width:55%;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:10px;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:8px;background:#fdecea;color:#c0392b;font-size:13px;font-weight:600;">
                                            ' . $nAtrasadas . ' atrasada(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span style="display:inline-block;padding:8px 12px;border-radius:8px;background:#fff4e5;color:#b45309;font-size:13px;font-weight:600;">
                                            ' . $nSemana . ' nesta semana
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="vertical-align:top;text-align:center;width:45%;">
                            <img src="' . $chartUrl . '" width="200" alt="Resumo visual" style="display:block;margin:0 auto;border:0;">
                        </td>
                    </tr>
                </table>
                ' . relatorioEmailTarefasHtml($atrasadas, 'Atrasadas', '#c0392b')
                . relatorioEmailTarefasHtml($semana, 'Planejadas para esta semana', '#b45309') . '
            </td>
        </tr>
    </table>';
}

function relatorioEmailLayout(
    string $nomeDestinatario,
    string $periodo,
    int $totalAtrasadas,
    int $totalSemana,
    string $blocosPropriedades
): string {
    $nome = relatorioEmailEsc($nomeDestinatario);
    $periodoEsc = relatorioEmailEsc($periodo);
    $url = relatorioEmailEsc(RELATORIO_APP_URL);

    return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo semanal — Caderno de Campo</title>
</head>
<body style="margin:0;padding:0;background:#eef2f1;font-family:Arial,Helvetica,sans-serif;color:#1f2d2a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f1;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="background:#0d7c74;border-radius:12px 12px 0 0;padding:28px 32px;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.85);">Frutag</div>
                            <div style="margin-top:6px;font-size:24px;font-weight:700;color:#ffffff;line-height:1.25;">Caderno de Campo</div>
                            <div style="margin-top:8px;font-size:14px;color:rgba(255,255,255,0.9);">Resumo semanal de apontamentos</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;padding:28px 32px 8px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#1f2d2a;">Olá, <strong>' . $nome . '</strong>,</p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#5f6b66;">
                                Este é o resumo das tarefas pendentes nas propriedades que você acompanha.
                                Período de referência: <strong style="color:#1f2d2a;">' . $periodoEsc . '</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;padding:8px 32px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:10px 0;">
                                <tr>
                                    <td width="50%" style="padding:16px;background:#fdecea;border-radius:10px;text-align:center;">
                                        <div style="font-size:28px;font-weight:700;color:#c0392b;line-height:1;">' . $totalAtrasadas . '</div>
                                        <div style="margin-top:6px;font-size:12px;font-weight:600;color:#7a2e28;text-transform:uppercase;letter-spacing:0.04em;">Atrasadas</div>
                                    </td>
                                    <td width="50%" style="padding:16px;background:#fff4e5;border-radius:10px;text-align:center;">
                                        <div style="font-size:28px;font-weight:700;color:#b45309;line-height:1;">' . $totalSemana . '</div>
                                        <div style="margin-top:6px;font-size:12px;font-weight:600;color:#8a5a12;text-transform:uppercase;letter-spacing:0.04em;">Nesta semana</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;padding:0 32px 28px;">
                            ' . $blocosPropriedades . '
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#0d7c74;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;">
                                            Abrir Caderno de Campo
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f7faf9;border-radius:0 0 12px 12px;padding:20px 32px;border-top:1px solid #e2e8e6;">
                            <p style="margin:0 0 8px;font-size:12px;line-height:1.5;color:#7a8682;">
                                Você recebe este e-mail porque autorizou comunicações do Caderno de Campo.
                                Para alterar suas preferências, acesse <strong>Dados pessoais</strong> no sistema.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#9aa5a1;">
                                Frutag · Caderno de Campo · Mensagem automática — não responda este e-mail.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function enviarEmail(string $para, string $nome, string $html): bool
{
    $smtpPassword = caderno_secret('SMTP_PASSWORD');
    if ($smtpPassword === null || $smtpPassword === '') {
        error_log('SMTP_PASSWORD não configurado — e-mail não enviado.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = caderno_secret('SMTP_HOST', 'mail.frutag.com.br');
        $mail->SMTPAuth   = true;
        $mail->Username   = caderno_secret('SMTP_USER', 'naoresponder@frutag.com.br');
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int) caderno_secret('SMTP_PORT', '465');

        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NOME);
        $mail->addAddress($para, $nome);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Caderno de Campo — Resumo semanal de apontamentos';
        $mail->Body    = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar email para {$para}: {$mail->ErrorInfo}");
        return false;
    }
}

function enviarRelatorioSemanal(): void
{
    global $mysqli;

    $hoje = new DateTime('today');
    $domingo = (clone $hoje)->modify('sunday this week');
    $periodo = $hoje->format('d/m/Y') . ' a ' . $domingo->format('d/m/Y');

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

        $blocosPropriedades = '';
        $totalAtrasadas = 0;
        $totalSemana = 0;

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

            if ($atrasadas === [] && $semana === []) {
                continue;
            }

            $totalAtrasadas += count($atrasadas);
            $totalSemana += count($semana);

            $blocosPropriedades .= relatorioEmailPropriedadeHtml(
                (string)$p['nome_razao'],
                $atrasadas,
                $semana
            );
        }

        if ($blocosPropriedades === '') {
            continue;
        }

        $html = relatorioEmailLayout(
            $dest['nome'],
            $periodo,
            $totalAtrasadas,
            $totalSemana,
            $blocosPropriedades
        );

        enviarEmail($dest['email'], $dest['nome'], $html);
    }
}
