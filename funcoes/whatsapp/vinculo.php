<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function waBuscarVinculoAtivo(mysqli $mysqli, string $waId): ?array
{
    $stmt = $mysqli->prepare('
        SELECT id, user_id, telefone_e164, wa_id
        FROM whatsapp_vinculos
        WHERE wa_id = ? AND ativo = 1
        LIMIT 1
    ');
    $stmt->bind_param('s', $waId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/** Busca user_id pelo telefone cadastrado em propriedades (telefone1/telefone2). */
function waBuscarUserPorTelefone(mysqli $mysqli, string $e164): ?int
{
    $digits = preg_replace('/\D/', '', $e164);
    if ($digits === '') {
        return null;
    }

    $stmt = $mysqli->prepare('
        SELECT user_id
        FROM propriedades
        WHERE ativo = 1
          AND (
            REPLACE(REPLACE(REPLACE(REPLACE(telefone1, " ", ""), "-", ""), "(", ""), ")", "") LIKE CONCAT("%", ?)
            OR REPLACE(REPLACE(REPLACE(REPLACE(telefone2, " ", ""), "-", ""), "(", ""), ")", "") LIKE CONCAT("%", ?)
          )
        ORDER BY id ASC
        LIMIT 1
    ');

    $tail = strlen($digits) >= 11 ? substr($digits, -11) : $digits;
    $stmt->bind_param('ss', $tail, $tail);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row['user_id'] : null;
}

function waCriarVinculo(mysqli $mysqli, int $user_id, string $e164, string $waId): void
{
    $stmt = $mysqli->prepare('
        INSERT INTO whatsapp_vinculos (user_id, telefone_e164, wa_id, opt_in_em, ativo)
        VALUES (?, ?, ?, NOW(), 1)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            telefone_e164 = VALUES(telefone_e164),
            ativo = 1,
            opt_in_em = NOW(),
            atualizado_em = NOW()
    ');
    $stmt->bind_param('iss', $user_id, $e164, $waId);
    $stmt->execute();
    $stmt->close();
}

function waResolverUsuario(mysqli $mysqli, string $waId): ?array
{
    $vinculo = waBuscarVinculoAtivo($mysqli, $waId);
    if ($vinculo) {
        return $vinculo;
    }

    $e164 = waNormalizeE164($waId);
    if (!$e164) {
        return null;
    }

    $user_id = waBuscarUserPorTelefone($mysqli, $e164);
    if (!$user_id) {
        return null;
    }

    waCriarVinculo($mysqli, $user_id, $e164, $waId);
    return waBuscarVinculoAtivo($mysqli, $waId);
}

function waSalvarSessao(mysqli $mysqli, string $waId, int $user_id, array $intent, array $resolucao, string $resumo, int $ttlMin = 30): void
{
    $intentJson = json_encode($intent, JSON_UNESCAPED_UNICODE);
    $resolucaoJson = json_encode($resolucao, JSON_UNESCAPED_UNICODE);

    $stmt = $mysqli->prepare('
        INSERT INTO whatsapp_sessoes (wa_id, user_id, intent_json, resolucao_json, resumo, expira_em)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            intent_json = VALUES(intent_json),
            resolucao_json = VALUES(resolucao_json),
            resumo = VALUES(resumo),
            expira_em = VALUES(expira_em)
    ');
    $stmt->bind_param('sisssi', $waId, $user_id, $intentJson, $resolucaoJson, $resumo, $ttlMin);
    $stmt->execute();
    $stmt->close();
}

function waCarregarSessao(mysqli $mysqli, string $waId): ?array
{
    waLimparSessoesExpiradas($mysqli);

    $stmt = $mysqli->prepare('
        SELECT user_id, intent_json, resolucao_json, resumo
        FROM whatsapp_sessoes
        WHERE wa_id = ? AND expira_em > NOW()
        LIMIT 1
    ');
    $stmt->bind_param('s', $waId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $intent = json_decode((string) $row['intent_json'], true);
    $resolucao = json_decode((string) $row['resolucao_json'], true);
    if (!is_array($intent) || !is_array($resolucao)) {
        return null;
    }

    return [
        'user_id' => (int) $row['user_id'],
        'intent' => $intent,
        'resolucao' => $resolucao,
        'resumo' => (string) $row['resumo'],
    ];
}

function waLimparSessao(mysqli $mysqli, string $waId): void
{
    $stmt = $mysqli->prepare('DELETE FROM whatsapp_sessoes WHERE wa_id = ?');
    $stmt->bind_param('s', $waId);
    $stmt->execute();
    $stmt->close();
}

function waLimparSessoesExpiradas(mysqli $mysqli): void
{
    $mysqli->query('DELETE FROM whatsapp_sessoes WHERE expira_em <= NOW()');
}

function waMensagemNaoVinculado(): string
{
    return implode("\n", [
        '👋 Olá! Este número ainda não está vinculado ao Caderno Frutag.',
        '',
        'Cadastre seu celular em *Propriedade* no sistema (telefone principal) e envie *VINCULAR* aqui.',
        '',
        'Ou fale com o suporte Frutag para liberar o piloto.',
    ]);
}
