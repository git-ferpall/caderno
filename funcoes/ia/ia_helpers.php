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
    $id = (int) $user_id;
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    return $id;
}

function iaOpenAiKey(): string
{
    $key = defined('OPENAI_API_KEY') ? trim((string) OPENAI_API_KEY) : '';
    if (stripos($key, 'Bearer ') === 0) {
        $key = trim(substr($key, 7));
    }
    if ($key === '') {
        throw new RuntimeException(
            'Assistente por voz não configurado. Defina OPENAI_API_KEY no servidor.'
        );
    }
    if (strlen($key) > 512 || str_contains($key, "\n") || str_contains($key, 'OPENAI_')) {
        throw new RuntimeException(
            'OPENAI_API_KEY inválida no servidor. Deve conter apenas a chave sk-… (sem outras variáveis no mesmo valor).'
        );
    }
    return $key;
}

function iaOpenAiCurlOpts($ch, array $headers): void
{
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROXY => '',
        CURLOPT_HTTPPROXYTUNNEL => false,
        CURLOPT_USERAGENT => 'CadernoFrutag-IA/1.0',
        CURLOPT_ENCODING => '',
    ]);
}

function iaOpenAiErroHttp(int $status, string $msg): never
{
    if ($status === 431) {
        throw new RuntimeException(
            'Erro 431: cabeçalhos HTTP grandes. Verifique OPENAI_API_KEY no .env (somente sk-…), '
            . 'limpe cookies do site e, no nginx, aumente large_client_header_buffers.'
        );
    }
    throw new RuntimeException('OpenAI erro ' . $status . ': ' . $msg);
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
    $headers[] = 'Accept: application/json';
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    iaOpenAiCurlOpts($ch, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

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
    if (!empty($payload['prompt'])) {
        $fields['prompt'] = (string) $payload['prompt'];
    }

    $ch = curl_init($url);
    iaOpenAiCurlOpts($ch, ['Authorization: Bearer ' . iaOpenAiKey()]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

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
        $msg = is_array($decoded) ? ($decoded['error']['message'] ?? $body) : (string) $body;
        $msg = trim(strip_tags(substr($msg, 0, 500)));
        iaOpenAiErroHttp($status, $msg !== '' ? $msg : 'erro desconhecido');
    }

    return is_array($decoded) ? $decoded : [];
}

function iaWhisperPrompt(?string $campoDialogo = null): string
{
    $base = 'Português do Brasil. Agricultura e hidroponia: plantio, semeadura, colheita, irrigação, '
        . 'herbicida, fungicida, inseticida, fertilizante, bancada, talhão, litros, mililitros.';

    return match ($campoDialogo) {
        'tipo' => $base . ' Tipos de manejo: plantio, semeadura, colheita, irrigação.',
        'area' => $base . ' Nomes de áreas, talhões e bancadas da propriedade.',
        'produto' => $base . ' Nomes de culturas e produtos agrícolas.',
        'insumo' => $base . ' Nomes de herbicidas, fungicidas e defensivos.',
        'quantidade' => $base . ' Quantidades em litros, quilos, bandejas, sementes, mudas, sacas.',
        'tipo_semeadura' => $base . ' Direta, bandeja, canteiro, replantio.',
        'previsao' => $base . ' Previsão de colheita em dias. Pular para não marcar.',
        'observacoes' => $base . ' Observações do manejo. Pular se não houver.',
        'data' => $base . ' Datas: hoje, ontem, ou dia e mês.',
        default => $base,
    };
}

/**
 * Corrige transcrições do Whisper que confundem pt-BR com pseudo-inglês.
 */
function iaCorrigirTranscricaoPt(string $texto, ?string $campoDialogo = null): string
{
    $t = trim($texto);
    if ($t === '') {
        return $t;
    }

    $correcoesGerais = [
        '/\bseeding\b/iu' => 'semeadura',
        '/\bharvest\b/iu' => 'colheita',
        '/\birrigation\b/iu' => 'irrigação',
        '/\bwatering\b/iu' => 'irrigação',
        '/\bbed\s*(?:one|1)\b/iu' => 'bancada 1',
        '/\bbed\s*(?:two|2)\b/iu' => 'bancada 2',
        '/\bbed\s*(?:three|3)\b/iu' => 'bancada 3',
    ];

    foreach ($correcoesGerais as $pattern => $replacement) {
        $t = preg_replace($pattern, $replacement, $t) ?? $t;
    }

    if ($campoDialogo !== 'area' && $campoDialogo !== 'produto') {
        $correcoesPlantio = [
            '/\bplan\s*(?:2|two|to|too)\b/iu' => 'plantio',
            '/\bplane?\s*two\b/iu' => 'plantio',
            '/\bplanta\s*(?:2|dois)\b/iu' => 'plantio',
            '/\bplant\s*(?:2|two|to)\b/iu' => 'plantio',
        ];
        foreach ($correcoesPlantio as $pattern => $replacement) {
            $t = preg_replace($pattern, $replacement, $t) ?? $t;
        }
        if ($campoDialogo === 'tipo' && preg_match('/\bplan\b/iu', $t) && !preg_match('/\bplant(?:io|ei|ar)\b/iu', $t)) {
            $t = preg_replace('/\bplan\b/iu', 'plantio', $t) ?? $t;
        }
    }

    return trim($t);
}

function iaTranscreverAudio(string $filePath, string $mime = 'audio/webm', ?string $campoDialogo = null): string
{
    $model = iaModel('OPENAI_WHISPER_MODEL', 'whisper-1');
    $resp = iaOpenAiRequest('/audio/transcriptions', [
        'model' => $model,
        'language' => 'pt',
        'response_format' => 'json',
        'prompt' => iaWhisperPrompt($campoDialogo),
    ], $filePath, $mime);

    $text = trim((string) ($resp['text'] ?? ''));
    if ($text === '') {
        throw new RuntimeException('Não foi possível transcrever o áudio. Tente falar mais perto do microfone.');
    }

    return iaCorrigirTranscricaoPt($text, $campoDialogo);
}

function iaInterpretarComando(string $transcricao, array $contexto, ?array $memoria = null): array
{
    $model = iaModel('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
    $hoje = date('Y-m-d');

    $system = <<<PROMPT
Você é o agente inteligente do Caderno Frutag — assistente de campo que REGISTRA, CONSULTA e GERENCIA manejos. Interprete SOMENTE pt-BR e responda SOMENTE JSON válido.

Schema:
{
  "acao": "criar_apontamento|concluir_apontamento|cancelar_apontamento|editar_apontamento|consultar|listar_pendentes|desconhecido",
  "consulta": "contar_pendentes|listar_pendentes|ultima_colheita|ultimo_manejo|resumo_manejos|total_colheita|colheita_por_produto|colheita_comparar|manejo_por_area|null",
  "periodo": "semana|mes|30_dias|7_dias|ano|null",
  "tipo": "irrigacao|colheita|semeadura|plantio|herbicida|fungicida|inseticida|fertilizante|personalizado|null",
  "apontamento_id": number ou null,
  "data": "YYYY-MM-DD ou null",
  "previsao_dias": number ou null,
  "insumo_nome": "string ou null",
  "area_nomes": ["string"],
  "produto_nomes": ["string"],
  "quantidade": number ou null,
  "unidade": "litros|kg|sementes|bandejas|mudas|caixas|sacas|m3|null",
  "tipo_semeadura": "Direta|Bandeja|Canteiro|Replantio|null",
  "tempo_irrigacao": number ou null,
  "observacoes": "string ou null",
  "confianca": 0.0 a 1.0,
  "mensagem": "resumo curto em português"
}

Ações:
- criar_apontamento: registrar manejo (faltando campos → diálogo completa).
- concluir_apontamento: marcar pendente como feito ("concluir irrigação", "marca como feito").
- cancelar_apontamento: desfazer/cancelar último apontamento.
- editar_apontamento: alterar/adicionar observação ("add obs no último", "editar observação").
- consultar: perguntas sobre pendentes, colheitas, resumos, comparativos.

Consultas:
- contar_pendentes, listar_pendentes, ultima_colheita, ultimo_manejo (+tipo), resumo_manejos (+periodo), total_colheita, colheita_por_produto (+produto_nomes), colheita_comparar, manejo_por_area (+area_nomes +tipo).

Use memoria_conversa para follow-ups ("marca o primeiro" após listar pendentes).
Hoje é {$hoje}. Perguntas ≠ criar_apontamento. Tom natural de agente prestativo.
PROMPT;

    require_once __DIR__ . '/contexto_usuario.php';
    require_once __DIR__ . '/memoria.php';
    $payload = [
        'transcricao' => $transcricao,
        'contexto' => iaContextoParaIa($contexto),
    ];
    if ($memoria) {
        $payload['memoria_conversa'] = iaMemoriaParaIa($memoria);
    }

    $userPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

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

/** Mantém só campos do intent no diálogo (evita POST gigante). */
function iaSanitizarIntentParcial(?array $intent): ?array
{
    if ($intent === null) {
        return null;
    }

    $intent = iaNormalizarIntent($intent);
    $permitidos = [
        'acao', 'tipo', 'consulta', 'periodo', 'data', 'area_nomes', 'produto_nomes', 'quantidade', 'unidade',
        'variedade', 'tipo_semeadura', 'tempo_irrigacao', 'unidade_tempo', 'titulo',
        'descricao', 'observacoes', 'previsao_dias', 'insumo_nome', 'apontamento_id', 'confianca', 'mensagem',
        '_data_respondida', '_previsao_respondida', '_obs_respondida', '_tempo_respondido',
        '_pendentes_opcao', '_concluir_tipo', '_cancel_confirmado',
    ];

    return array_intersect_key($intent, array_flip($permitidos));
}

function iaNormalizarIntent(array $intent): array
{
    $defaults = [
        'acao' => 'desconhecido',
        'consulta' => null,
        'periodo' => null,
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
        'previsao_dias' => null,
        'insumo_nome' => null,
        'apontamento_id' => null,
        '_tempo_respondido' => false,
        '_cancel_confirmado' => false,
        '_data_respondida' => false,
        '_previsao_respondida' => false,
        '_obs_respondida' => false,
        'apontamento_ref' => null,
        'confianca' => 0.3,
        'mensagem' => 'Não entendi o comando.',
    ];

    $intent = array_merge($defaults, $intent);

    $acoes = ['criar_apontamento', 'concluir_apontamento', 'cancelar_apontamento', 'editar_apontamento', 'listar_pendentes', 'consultar', 'desconhecido'];
    $intent['acao'] = in_array($intent['acao'], $acoes, true) ? $intent['acao'] : 'desconhecido';

    if (!empty($intent['apontamento_id'])) {
        $intent['apontamento_id'] = (int) $intent['apontamento_id'];
    }

    if ($intent['acao'] === 'consultar') {
        require_once __DIR__ . '/consultas.php';
        $intent = iaNormalizarIntentConsulta($intent);
    }

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
