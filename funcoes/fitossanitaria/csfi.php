<?php
declare(strict_types=1);

require_once __DIR__ . '/agrofit.php';

function fsTabelaCsfiExiste(mysqli $mysqli): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $res = $mysqli->query("SHOW TABLES LIKE 'csfi_culturas'");
    $cache = $res && $res->num_rows > 0;
    return $cache;
}

function fsVerificarCsfi(mysqli $mysqli, string $culturaNome): array
{
    if (trim($culturaNome) === '') {
        return [
            'csfi' => null,
            'status' => 'sem_cultura',
            'resumo' => 'Nenhuma cultura vinculada à área nos apontamentos.',
        ];
    }

    if (!fsTabelaCsfiExiste($mysqli)) {
        return [
            'csfi' => null,
            'status' => 'nao_verificado',
            'resumo' => 'Base CSFI não instalada. Execute fitossanitaria_fase3.sql',
        ];
    }

    $norm = fsNormalizarNome($culturaNome);
    try {
        $stmt = $mysqli->prepare('
            SELECT id, nome, observacao
            FROM csfi_culturas
            WHERE ativo = 1 AND (
                nome_normalizado = ? OR nome_normalizado LIKE ? OR ? LIKE CONCAT("%", nome_normalizado, "%")
            )
            LIMIT 1
        ');
        $like = '%' . $norm . '%';
        $stmt->bind_param('sss', $norm, $like, $norm);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('fitossanitaria csfi: ' . $e->getMessage());
        return [
            'csfi' => null,
            'status' => 'nao_verificado',
            'resumo' => 'Erro ao consultar base CSFI. Execute fitossanitaria_fase3.sql.',
        ];
    }

    if ($row) {
        return [
            'csfi' => true,
            'status' => 'csfi',
            'cultura' => (string) $row['nome'],
            'observacao' => (string) ($row['observacao'] ?? ''),
            'resumo' => 'Cultura CSFI/Minor Crop: ' . $row['nome'] . '. Exige validação técnica do responsável.',
        ];
    }

    return [
        'csfi' => false,
        'status' => 'convencional',
        'cultura' => $culturaNome,
        'resumo' => 'Cultura não classificada como CSFI na base local.',
    ];
}

/** @param string[] $culturas */
function fsVerificarCsfiCulturas(mysqli $mysqli, array $culturas): array
{
    if (!$culturas) {
        return fsVerificarCsfi($mysqli, '');
    }

    $resultados = [];
    $algumCsfi = false;
    foreach ($culturas as $c) {
        $r = fsVerificarCsfi($mysqli, $c);
        $resultados[] = $r;
        if (!empty($r['csfi'])) {
            $algumCsfi = true;
        }
    }

    $principal = $resultados[0];
    if ($algumCsfi) {
        $csfiNomes = array_values(array_filter(array_map(
            fn ($r) => $r['cultura'] ?? null,
            array_filter($resultados, fn ($r) => !empty($r['csfi']))
        )));
        return [
            'csfi' => true,
            'status' => 'csfi',
            'culturas' => $csfiNomes,
            'resumo' => 'Área com cultura(s) CSFI: ' . implode(', ', $csfiNomes) . '. Validação do agrônomo obrigatória.',
            'detalhes' => $resultados,
        ];
    }

    return array_merge($principal, ['detalhes' => $resultados]);
}
