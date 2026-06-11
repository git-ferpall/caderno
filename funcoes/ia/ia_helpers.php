<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

function iaJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function iaAuthUserId(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }
    if (!$user_id) {
        iaJson(['ok' => false, 'err' => 'Usuário não autenticado.'], 401);
    }
    return (int) $user_id;
}

function iaOpenAiKey(): string
{
    $key = defined('OPENAI_API_KEY') ? (string) OPENAI_API_KEY : '';
    if ($key === '') {
        throw new RuntimeException(
            'Assistente por voz não configurado. Defina OPENAI_API_KEY no servidor.'
        );
    }
    return $key;
}

function iaModel(string $constant, string $default): string
{
    if (defined($constant)) {
        $value = trim((string) constant($constant));
        if ($value !== '') {
            return $value;
        }
    }
    return $default;
}

function iaApiBase(): string
{
    $base = iaModel('OPENAI_API_BASE', 'https://api.openai.com/v1');
    if (!preg_match('#^https?://#i', $base)) {
        return 'https://api.openai.com/v1';
    }
    return rtrim($base, '/');
}

function iaOpenAiRequest(string $endpoint, array $payload, ?string $multipartPath = null, ?string $multipartMime = null): array
{
    $url = iaApiBase() . $endpoint;
    $ch = curl_init($url);

    $headers = ['Authorization: Bearer ' . iaOpenAiKey()];

    if ($multipartPath !== null) {
        return iaOpenAiMultipart($url, $payload, $multipartPath, $multipartMime ?: 'audio/webm');
    }

    if (empty($payload['model'])) {
        throw new RuntimeException('Parâmetro model ausente na requisição OpenAI.');
    }

    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 90,
    ]);

    return iaCurlExec($ch);
}

function iaOpenAiMultipart(string $url, array $payload, string $filePath, string $mime): array
{
    if (!is_readable($filePath)) {
        throw new RuntimeException('Arquivo de áudio não legível no servidor.');
    }

    $model = trim((string) ($payload['model'] ?? ''));
    if ($model === '') {
        throw new RuntimeException('Modelo Whisper não configurado.');
    }

    $file = function_exists('curl_file_create')
        ? curl_file_create($filePath, $mime, 'audio.webm')
        : new CURLFile($filePath, $mime, 'audio.webm');

    $fields = [
        'model' => $model,
        'file' => $file,
    ];

    if (!empty($payload['language'])) {
        $fields['language'] = (string) $payload['language'];
    }
    if (!empty($payload['response_format'])) {
        $fields['response_format'] = (string) $payload['response_format'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . iaOpenAiKey()],
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_TIMEOUT => 120,
    ]);

    return iaCurlExec($ch);
}

function iaCurlExec($ch): array
{
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('Falha na comunicação com OpenAI: ' . $error);
    }

    $decoded = json_decode((string) $body, true);
    if ($status >= 400) {
        $msg = is_array($decoded) ? ($decoded['error']['message'] ?? $body) : $body;
        throw new RuntimeException('OpenAI erro ' . $status . ': ' . $msg);
    }

    return is_array($decoded) ? $decoded : [];
}

function iaTranscreverAudio(string $filePath, string $mime = 'audio/webm'): string
{
    $model = iaModel('OPENAI_WHISPER_MODEL', 'whisper-1');
    $resp = iaOpenAiRequest('/audio/transcriptions', [
        'model' => $model,
        'language' => 'pt',
        'response_format' => 'json',
    ], $filePath, $mime);

    $text = trim((string) ($resp['text'] ?? ''));
    if ($text === '') {
        throw new RuntimeException('Não foi possível transcrever o áudio. Tente falar mais perto do microfone.');
    }
    return $text;
}

function iaInterpretarComando(string $transcricao, array $contexto): array
{
    $model = iaModel('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
    $hoje = date('Y-m-d');

    $system = <<<PROMPT
Você é o assistente do Caderno Frutag (agricultura/hidroponia). Interprete comandos de voz em português e responda SOMENTE com JSON válido (sem markdown).

Schema:
{
  "acao": "criar_apontamento" | "concluir_apontamento" | "listar_pendentes" | "desconhecido",
  "tipo": "irrigacao|colheita|semeadura|plantio|personalizado|...",
  "data": "YYYY-MM-DD ou null",
  "area_nomes": ["string"],
  "produto_nomes": ["string"],
  "quantidade": number ou null,
  "unidade": "litros|kg|sementes|bandejas|mudas|caixas|sacas|m3|horas|minutos|null",
  "variedade": "string ou null",
  "tipo_semeadura": "Direta|Bandeja|Canteiro|Replantio|null",
  "tempo_irrigacao": number ou null,
  "unidade_tempo": "horas|minutos|null",
  "titulo": "string ou null",
  "descricao": "string ou null",
  "observacoes": "string ou null",
  "apontamento_ref": {"tipo":"string","area_nome":"string","produto_nome":"string ou null","data":"YYYY-MM-DD ou null"} ou null,
  "confianca": 0.0 a 1.0,
  "mensagem": "resumo curto para o usuário em português"
}

Regras:
- Hoje é {$hoje}.
- criar_apontamento: lançar/registrar/aplicar irrigação, colheita, semeadura etc.
- concluir_apontamento: marcar como feito/concluído um pendente.
- listar_pendentes: perguntas sobre o que falta fazer.
- Use nomes de áreas/produtos do contexto quando possível.
- Irrigação: quantidade = volume (litros/m3), tempo_irrigacao opcional.
- Colheita: quantidade + unidade kg/caixas.
- Semeadura: quantidade, unidade sementes/bandejas/kg, variedade, tipo_semeadura.
- Se faltar info crítica, acao=desconhecido e confianca baixa.
PROMPT;

    $userPayload = json_encode([
        'transcricao' => $transcricao,
        'contexto' => $contexto,
    ], JSON_UNESCAPED_UNICODE);

    $resp = iaOpenAiRequest('/chat/completions', [
        'model' => $model,
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.2,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPayload],
        ],
    ]);

    $content = $resp['choices'][0]['message']['content'] ?? '{}';
    $intent = json_decode((string) $content, true);
    if (!is_array($intent)) {
        throw new RuntimeException('Resposta inválida da IA.');
    }

    return iaNormalizarIntent($intent);
}

function iaNormalizarIntent(array $intent): array
{
    $defaults = [
        'acao' => 'desconhecido',
        'tipo' => null,
        'data' => date('Y-m-d'),
        'area_nomes' => [],
        'produto_nomes' => [],
        'quantidade' => null,
        'unidade' => null,
        'variedade' => null,
        'tipo_semeadura' => null,
        'tempo_irrigacao' => null,
        'unidade_tempo' => null,
        'titulo' => null,
        'descricao' => null,
        'observacoes' => null,
        'apontamento_ref' => null,
        'confianca' => 0.3,
        'mensagem' => 'Não entendi o comando.',
    ];

    $intent = array_merge($defaults, $intent);

    $intent['acao'] = in_array($intent['acao'], ['criar_apontamento', 'concluir_apontamento', 'listar_pendentes', 'desconhecido'], true)
        ? $intent['acao'] : 'desconhecido';

    foreach (['area_nomes', 'produto_nomes'] as $k) {
        if (!is_array($intent[$k])) {
            $intent[$k] = $intent[$k] ? [(string) $intent[$k]] : [];
        }
    }

    $intent['confianca'] = max(0.0, min(1.0, (float) $intent['confianca']));

    if ($intent['data'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $intent['data'])) {
        $intent['data'] = date('Y-m-d');
    }

    $tiposSemeadura = ['Direta', 'Bandeja', 'Canteiro', 'Replantio'];
    if ($intent['tipo_semeadura'] && !in_array($intent['tipo_semeadura'], $tiposSemeadura, true)) {
        $intent['tipo_semeadura'] = null;
    }

    return $intent;
}
