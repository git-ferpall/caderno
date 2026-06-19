<?php
declare(strict_types=1);

function fsTabelaLotesExiste(mysqli $mysqli): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $mysqli->query("SHOW TABLES LIKE 'fitossanitaria_lotes'");
    $cache = $res && $res->num_rows > 0;
    return $cache;
}

function fsGerarCodigoLote(int $propriedadeId, int $areaId): string
{
    return sprintf('FR-%04d-%04d-%s', $propriedadeId, $areaId, strtoupper(substr(md5($propriedadeId . '-' . $areaId), 0, 4)));
}

function fsStatusLoteFromScore(string $nivelScore): string
{
    return match ($nivelScore) {
        'VERDE' => 'liberado',
        'AMARELO' => 'atencao',
        'VERMELHO' => 'bloqueado',
        default => 'indefinido',
    };
}

function fsCalcularHashAuditoria(array $payload): string
{
    ksort($payload);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    return hash('sha256', (string) $json);
}

function fsUrlVerificacaoLote(string $codigoLote, string $hash): string
{
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'caderno.frutag.com.br');
    return $base . '/home/verificar_lote.php?codigo=' . rawurlencode($codigoLote) . '&hash=' . rawurlencode($hash);
}

function fsUrlQrCode(string $data, int $size = 180): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
        . '&data=' . rawurlencode($data);
}

/**
 * Cria ou atualiza lote Frutag da área com hash de auditoria.
 */
function fsObterOuAtualizarLote(
    mysqli $mysqli,
    int $propriedadeId,
    int $areaId,
    array $painel
): ?array {
    if (!fsTabelaLotesExiste($mysqli)) {
        return null;
    }

    $score = $painel['score'] ?? [];
    $nivel = (string) ($score['nivel'] ?? 'CINZA');
    $status = fsStatusLoteFromScore($nivel);

    $payload = [
        'codigo_area' => $areaId,
        'propriedade_id' => $propriedadeId,
        'area' => $painel['area'] ?? null,
        'data_referencia' => $painel['data_referencia'] ?? date('Y-m-d'),
        'score' => $score,
        'carencias_ativas' => $painel['status_carencia']['ativas'] ?? [],
        'csfi' => $painel['csfi'] ?? null,
        'clima' => [
            'nivel' => $painel['clima']['nivel'] ?? null,
            'aplicacao_recomendada' => $painel['clima']['aplicacao_recomendada'] ?? null,
        ],
        'agrofit_alertas' => $painel['agrofit']['alertas'] ?? [],
        'atualizado_em' => date('c'),
    ];

    $hash = fsCalcularHashAuditoria($payload);
    $codigo = fsGerarCodigoLote($propriedadeId, $areaId);
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    try {
        $stmt = $mysqli->prepare('
            INSERT INTO fitossanitaria_lotes
                (propriedade_id, area_id, codigo_lote, hash_auditoria, score_nivel, status_lote, payload_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hash_auditoria = VALUES(hash_auditoria),
                score_nivel = VALUES(score_nivel),
                status_lote = VALUES(status_lote),
                payload_json = VALUES(payload_json),
                atualizado_em = NOW()
        ');

        $stmt->bind_param('iisssss', $propriedadeId, $areaId, $codigo, $hash, $nivel, $status, $payloadJson);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('fitossanitaria lote INSERT: ' . $e->getMessage());
        return null;
    }

    $urlVerificacao = fsUrlVerificacaoLote($codigo, $hash);

    return [
        'codigo_lote' => $codigo,
        'hash_auditoria' => $hash,
        'hash_curto' => substr($hash, 0, 12) . '…',
        'score_nivel' => $nivel,
        'status_lote' => $status,
        'status_label' => match ($status) {
            'liberado' => 'Liberado',
            'atencao' => 'Atenção',
            'bloqueado' => 'Bloqueado',
            default => 'Indefinido',
        },
        'url_verificacao' => $urlVerificacao,
        'url_qrcode' => fsUrlQrCode($urlVerificacao),
        'atualizado_em' => date('Y-m-d H:i:s'),
    ];
}

function fsBuscarLotePorCodigo(mysqli $mysqli, string $codigo, ?string $hash = null): ?array
{
    if (!fsTabelaLotesExiste($mysqli)) {
        return null;
    }

    if ($hash) {
        $stmt = $mysqli->prepare('
            SELECT l.*, a.nome AS area_nome, p.nome_razao AS propriedade_nome
            FROM fitossanitaria_lotes l
            INNER JOIN areas a ON a.id = l.area_id
            INNER JOIN propriedades p ON p.id = l.propriedade_id
            WHERE l.codigo_lote = ? AND l.hash_auditoria = ?
            LIMIT 1
        ');
        $stmt->bind_param('ss', $codigo, $hash);
    } else {
        $stmt = $mysqli->prepare('
            SELECT l.*, a.nome AS area_nome, p.nome_razao AS propriedade_nome
            FROM fitossanitaria_lotes l
            INNER JOIN areas a ON a.id = l.area_id
            INNER JOIN propriedades p ON p.id = l.propriedade_id
            WHERE l.codigo_lote = ?
            LIMIT 1
        ');
        $stmt->bind_param('s', $codigo);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);

    return [
        'codigo_lote' => (string) $row['codigo_lote'],
        'hash_auditoria' => (string) $row['hash_auditoria'],
        'score_nivel' => (string) ($row['score_nivel'] ?? ''),
        'status_lote' => (string) ($row['status_lote'] ?? ''),
        'area_nome' => (string) ($row['area_nome'] ?? ''),
        'propriedade_nome' => (string) ($row['propriedade_nome'] ?? ''),
        'atualizado_em' => (string) ($row['atualizado_em'] ?? $row['criado_em'] ?? ''),
        'payload' => is_array($payload) ? $payload : [],
        'integridade_ok' => $hash === null || hash_equals((string) $row['hash_auditoria'], $hash),
    ];
}
