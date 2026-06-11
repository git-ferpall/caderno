<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/env.php';

function waLog(string $msg): void
{
    error_log('[whatsapp] ' . $msg);
}

function waConfig(string $key): string
{
    $map = [
        'token' => defined('WHATSAPP_TOKEN') ? (string) WHATSAPP_TOKEN : '',
        'phone_id' => defined('WHATSAPP_PHONE_NUMBER_ID') ? (string) WHATSAPP_PHONE_NUMBER_ID : '',
        'verify' => defined('WHATSAPP_VERIFY_TOKEN') ? (string) WHATSAPP_VERIFY_TOKEN : '',
        'secret' => defined('WHATSAPP_APP_SECRET') ? (string) WHATSAPP_APP_SECRET : '',
        'version' => defined('WHATSAPP_API_VERSION') ? (string) WHATSAPP_API_VERSION : 'v21.0',
    ];
    return $map[$key] ?? '';
}

function waIsConfigured(): bool
{
    return waConfig('token') !== '' && waConfig('phone_id') !== '';
}

/** Normaliza wa_id ou telefone brasileiro para E.164 (+5511...). */
function waNormalizeE164(string $input): ?string
{
    $num = preg_replace('/\D/', '', $input);
    if ($num === '') {
        return null;
    }

    if (str_starts_with($num, '55') && strlen($num) >= 12 && strlen($num) <= 13) {
        return '+' . $num;
    }

    if (strlen($num) === 11) {
        return '+55' . $num;
    }

    if (strlen($num) === 10) {
        return '+55' . $num;
    }

    return null;
}

function waWaIdFromE164(string $e164): string
{
    return ltrim($e164, '+');
}

function waVerifyWebhookSignature(string $rawBody): bool
{
    $secret = waConfig('secret');
    if ($secret === '') {
        return true;
    }

    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if ($sig === '' || !str_starts_with($sig, 'sha256=')) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
    return hash_equals($expected, $sig);
}

function waApiRequest(string $method, string $path, ?array $body = null): array
{
    if (!waIsConfigured()) {
        throw new RuntimeException('WhatsApp não configurado (WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID).');
    }

    $url = 'https://graph.facebook.com/' . waConfig('version') . $path;
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . waConfig('token'),
        'Content-Type: application/json',
    ];

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ];

    $method = strtoupper($method);
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body ?? [], JSON_UNESCAPED_UNICODE);
    } elseif ($method !== 'GET') {
        $opts[CURLOPT_CUSTOMREQUEST] = $method;
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
    }

    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('WhatsApp API: ' . $error);
    }

    $decoded = json_decode((string) $response, true);
    if ($status >= 400) {
        $msg = is_array($decoded) ? ($decoded['error']['message'] ?? $response) : $response;
        throw new RuntimeException('WhatsApp API erro ' . $status . ': ' . $msg);
    }

    return is_array($decoded) ? $decoded : [];
}

function waSendText(string $waId, string $text): void
{
    $text = trim($text);
    if ($text === '') {
        return;
    }

    if (mb_strlen($text) > 4000) {
        $text = mb_substr($text, 0, 3997) . '...';
    }

    waApiRequest('POST', '/' . waConfig('phone_id') . '/messages', [
        'messaging_product' => 'whatsapp',
        'to' => $waId,
        'type' => 'text',
        'text' => ['body' => $text],
    ]);
}

function waDownloadMedia(string $mediaId): array
{
    $meta = waApiRequest('GET', '/' . $mediaId);
    $url = $meta['url'] ?? '';
    if ($url === '') {
        throw new RuntimeException('URL de mídia WhatsApp ausente.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . waConfig('token')],
        CURLOPT_TIMEOUT => 90,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $binary = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno || $status >= 400 || $binary === false) {
        throw new RuntimeException('Falha ao baixar áudio WhatsApp: ' . ($error ?: 'HTTP ' . $status));
    }

    $mime = (string) ($meta['mime_type'] ?? 'audio/ogg');
    return ['binary' => $binary, 'mime' => $mime];
}

function waHelpMessage(): string
{
    return implode("\n", [
        '📋 *Caderno Frutag — Agente de campo*',
        '',
        '*Registrar:* áudio ou texto',
        '• "Herbicida ontem na bancada 1"',
        '• "Colheita de alface, 10 kg"',
        '',
        '*Consultar:*',
        '• "Quantos pendentes tenho?"',
        '• "Quanto colhi na última colheita?"',
        '• "Resumo do mês"',
        '',
        '*Gerenciar:*',
        '• "Concluir irrigação pendente"',
        '• "Marca o primeiro como feito"',
        '• "Editar observação do último"',
        '• "Cancelar último apontamento"',
        '',
        'Confirmação: *SIM* ou *NÃO*',
        'Comandos: *AJUDA* | *RESUMO* | *VINCULAR*',
    ]);
}

function waIsSim(string $text): bool
{
    $t = mb_strtolower(trim($text));
    return in_array($t, ['sim', 's', 'confirmar', 'confirmo', 'ok', 'pode', 'yes'], true);
}

function waIsNao(string $text): bool
{
    $t = mb_strtolower(trim($text));
    return in_array($t, ['nao', 'não', 'n', 'cancelar', 'cancela', 'no'], true);
}

/** Envia briefing uma vez por dia quando usuário cumprimenta. */
function waBriefingSeSaudacao(mysqli $mysqli, string $waId, int $user_id, string $texto): void
{
    $t = mb_strtolower(trim($texto));
    if (!preg_match('/^(oi|olá|ola|bom dia|boa tarde|boa noite|e aí|eai|hey|hello)\b/u', $t)) {
        return;
    }

    $flag = sys_get_temp_dir() . '/wa_brief_' . md5($waId . date('Y-m-d')) . '.flag';
    if (is_file($flag)) {
        return;
    }

    require_once __DIR__ . '/../ia/briefing.php';
    $msg = iaGerarBriefing($mysqli, $user_id);
    waSendText($waId, '🌱 ' . $msg);
    @file_put_contents($flag, '1');
}
