<?php
declare(strict_types=1);

require_once __DIR__ . '/resolver_entidades.php';
require_once __DIR__ . '/dialogo.php';
require_once __DIR__ . '/consultas.php';

/** Memória curta do agente (sessão PHP) para follow-up e contexto GPT. */
function iaMemoriaCarregar(int $user_id): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $key = 'ia_agente_' . $user_id;
    $mem = $_SESSION[$key] ?? [];
    if (!is_array($mem)) {
        return iaMemoriaVazia();
    }
    return array_merge(iaMemoriaVazia(), $mem);
}

function iaMemoriaVazia(): array
{
    return [
        'turnos' => [],
        'ultima_consulta' => null,
        'ultimos_pendentes' => [],
        'ultimo_apontamento_id' => null,
        'ultima_acao' => null,
    ];
}

function iaMemoriaSalvar(int $user_id, array $memoria): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $memoria['turnos'] = array_slice($memoria['turnos'] ?? [], -6);
    $_SESSION['ia_agente_' . $user_id] = $memoria;
}

function iaMemoriaRegistrarTurno(int $user_id, string $usuario, string $assistente, array $extra = []): void
{
    $mem = iaMemoriaCarregar($user_id);
    $mem['turnos'][] = [
        'u' => mb_substr(trim($usuario), 0, 200),
        'a' => mb_substr(trim($assistente), 0, 400),
        't' => time(),
    ];
    if (!empty($extra['consulta'])) {
        $mem['ultima_consulta'] = (string) $extra['consulta'];
    }
    if (!empty($extra['pendentes'])) {
        $mem['ultimos_pendentes'] = $extra['pendentes'];
    }
    if (!empty($extra['apontamento_id'])) {
        $mem['ultimo_apontamento_id'] = (int) $extra['apontamento_id'];
    }
    if (!empty($extra['acao'])) {
        $mem['ultima_acao'] = (string) $extra['acao'];
    }
    iaMemoriaSalvar($user_id, $mem);
}

/** Contexto compacto para o GPT interpretar follow-ups. */
function iaMemoriaParaIa(array $memoria): array
{
    $turnos = array_slice($memoria['turnos'] ?? [], -4);
    $ctx = [
        'turnos_recentes' => array_map(static fn ($t) => [
            'usuario' => $t['u'] ?? '',
            'assistente' => $t['a'] ?? '',
        ], $turnos),
        'ultima_consulta' => $memoria['ultima_consulta'] ?? null,
        'ultima_acao' => $memoria['ultima_acao'] ?? null,
    ];
    $pend = $memoria['ultimos_pendentes'] ?? [];
    if ($pend) {
        $ctx['pendentes_recentes'] = array_map(static fn ($p) => [
            'id' => (int) ($p['id'] ?? 0),
            'tipo' => $p['tipo'] ?? '',
            'areas' => $p['areas'] ?? '',
            'produto' => $p['produto'] ?? '',
            'data' => $p['data'] ?? '',
        ], array_slice($pend, 0, 5));
    }
    return $ctx;
}

/**
 * Repara follow-ups locais ("marca o primeiro", "conclui", "detalha").
 */
function iaRepararIntentFollowUp(array $intent, string $texto, array $memoria): array
{
    $t = iaNormalizarTexto($texto);
    $pendentes = $memoria['ultimos_pendentes'] ?? [];

    if (preg_match('/\b(?:marca|conclu|finaliz|feito|feita|pronto|pronta)\b/u', $t)
        && ($pendentes || ($memoria['ultima_consulta'] ?? '') !== '')) {
        $intent['acao'] = 'concluir_apontamento';
        $intent['confianca'] = 0.92;

        if (preg_match('/\b(?:primeir[oa]|1|um)\b/u', $t) && !empty($pendentes[0]['id'])) {
            $intent['apontamento_id'] = (int) $pendentes[0]['id'];
        } elseif (preg_match('/\b(?:segund[oa]|2|dois)\b/u', $t) && !empty($pendentes[1]['id'])) {
            $intent['apontamento_id'] = (int) $pendentes[1]['id'];
        } elseif (preg_match('/\b(?:terceir[oa]|3|tr[eê]s)\b/u', $t) && !empty($pendentes[2]['id'])) {
            $intent['apontamento_id'] = (int) $pendentes[2]['id'];
        }

        $tipo = iaNormalizarTipoManejo($texto);
        if ($tipo) {
            $intent['tipo'] = $tipo;
        }
        $intent['mensagem'] = 'Vou marcar como concluído.';
        return $intent;
    }

    if (preg_match('/\b(?:detalh|listar|mostr|quais)\b/u', $t)
        && in_array($memoria['ultima_consulta'] ?? '', ['contar_pendentes', 'listar_pendentes'], true)) {
        return iaIntentConsulta('listar_pendentes', $intent);
    }

    if (preg_match('/\b(?:desfaz|cancela|apaga|exclu|remove|desfa[cç]a)\b/u', $t)
        && preg_match('/\b(?:ultim[ao]|últim[oa]|que acabei|que lancei)\b/u', $t)) {
        $intent['acao'] = 'cancelar_apontamento';
        $intent['confianca'] = 0.9;
        if (!empty($memoria['ultimo_apontamento_id'])) {
            $intent['apontamento_id'] = (int) $memoria['ultimo_apontamento_id'];
        }
        $intent['mensagem'] = 'Quer cancelar o último apontamento?';
        return $intent;
    }

    if (preg_match('/\b(?:editar|alterar|mudar|acrescent|adicion).*(?:obs|observa)/u', $t)
        || preg_match('/\b(?:obs|observa).*(?:ultim[ao]|apontamento)/u', $t)) {
        $intent['acao'] = 'editar_apontamento';
        $intent['confianca'] = 0.88;
        if (!empty($memoria['ultimo_apontamento_id'])) {
            $intent['apontamento_id'] = (int) $memoria['ultimo_apontamento_id'];
        }
        if (preg_match('/(?:obs|observa)[^.]*(?:dizendo|falando|:)\s*(.+)$/u', $texto, $m)) {
            $intent['observacoes'] = trim($m[1]);
        }
        return $intent;
    }

    if (preg_match('/\b(?:conclu|marca|finaliz)\b/u', $t) && ($intent['acao'] ?? '') === 'desconhecido') {
        $intent['acao'] = 'concluir_apontamento';
        $intent['confianca'] = 0.85;
        $tipo = iaNormalizarTipoManejo($texto);
        if ($tipo) {
            $intent['tipo'] = $tipo;
        }
    }

    return $intent;
}

function iaMemoriaExtrairExtrasResultado(array $resultado): array
{
    $extra = [];
    if (!empty($resultado['consulta'])) {
        $extra['consulta'] = $resultado['consulta'];
    }
    $dados = $resultado['consulta_dados'] ?? null;
    if (is_array($dados)) {
        if (!empty($dados['pendentes'])) {
            $extra['pendentes'] = $dados['pendentes'];
        } elseif (!empty($dados['amostra'])) {
            $extra['pendentes'] = $dados['amostra'];
        }
    }
    if (!empty($resultado['apontamento_id'])) {
        $extra['apontamento_id'] = (int) $resultado['apontamento_id'];
    }
    if (!empty($resultado['intent']['acao'])) {
        $extra['acao'] = $resultado['intent']['acao'];
    }
    return $extra;
}
