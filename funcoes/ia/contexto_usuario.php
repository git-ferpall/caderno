<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../apontamento_arquivos.php';

function iaContextoUsuario(mysqli $mysqli, int $user_id): array
{
    $prop = obterPropriedadeAtiva($mysqli, $user_id);
    if (!$prop) {
        return [
            'propriedade' => null,
            'areas' => [],
            'produtos' => [],
            'pendentes' => [],
            'tipos_manejo' => iaTiposManejo(),
        ];
    }

    $propriedade_id = (int) $prop['id'];

    $areas = [];
    $stmt = $mysqli->prepare('SELECT id, nome, tipo FROM areas WHERE user_id = ? AND propriedade_id = ? ORDER BY nome ASC');
    $stmt->bind_param('ii', $user_id, $propriedade_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $areas[] = ['id' => (int) $row['id'], 'nome' => $row['nome'], 'tipo' => $row['tipo']];
    }
    $stmt->close();

    $produtos = [];
    $stmt = $mysqli->prepare('SELECT id, nome FROM produtos WHERE user_id = ? ORDER BY nome ASC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $produtos[] = ['id' => (int) $row['id'], 'nome' => $row['nome']];
    }
    $stmt->close();

    $pendentes = iaListarPendentesResumo($mysqli, $propriedade_id, 15);

    return [
        'propriedade' => ['id' => $propriedade_id, 'nome' => $prop['nome_razao'] ?? ''],
        'areas' => $areas,
        'produtos' => $produtos,
        'pendentes' => $pendentes,
        'tipos_manejo' => iaTiposManejo(),
        'hoje' => date('Y-m-d'),
    ];
}

function iaTiposManejo(): array
{
    return [
        'irrigacao' => 'Irrigação',
        'colheita' => 'Colheita',
        'semeadura' => 'Semeadura',
        'plantio' => 'Plantio',
        'personalizado' => 'Personalizado',
    ];
}

function iaListarPendentesResumo(mysqli $mysqli, int $propriedade_id, int $limite = 15): array
{
    $sql = "
        SELECT 
            a.id,
            a.tipo,
            a.data,
            GROUP_CONCAT(DISTINCT ar.nome SEPARATOR ', ') AS areas,
            (
                SELECT p.nome
                FROM apontamento_detalhes ad2
                JOIN produtos p ON p.id = ad2.valor
                WHERE ad2.apontamento_id = a.id 
                  AND (ad2.campo = 'produto' OR ad2.campo = 'produto_id')
                LIMIT 1
            ) AS produto_nome
        FROM apontamentos a
        LEFT JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id AND ad.campo = 'area_id'
        LEFT JOIN areas ar ON ar.id = ad.valor
        WHERE a.propriedade_id = ? AND a.status = 'pendente'
        GROUP BY a.id
        ORDER BY a.data DESC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $propriedade_id, $limite);
    $stmt->execute();
    $res = $stmt->get_result();

    $itens = [];
    while ($row = $res->fetch_assoc()) {
        $itens[] = [
            'id' => (int) $row['id'],
            'tipo' => $row['tipo'],
            'data' => $row['data'],
            'areas' => $row['areas'] ?: '',
            'produto' => $row['produto_nome'] ?: '',
        ];
    }
    $stmt->close();

    return $itens;
}

/** Contexto reduzido para a OpenAI (evita payload enorme). */
function iaContextoParaIa(array $contexto, int $maxAreas = 40, int $maxProdutos = 40): array
{
    $areas = array_slice($contexto['areas'] ?? [], 0, $maxAreas);
    $produtos = array_slice($contexto['produtos'] ?? [], 0, $maxProdutos);

    return [
        'propriedade' => ($contexto['propriedade']['nome'] ?? null) ?: null,
        'areas' => array_values(array_map(static fn ($a) => (string) ($a['nome'] ?? ''), $areas)),
        'produtos' => array_values(array_map(static fn ($p) => (string) ($p['nome'] ?? ''), $produtos)),
        'hoje' => (string) ($contexto['hoje'] ?? date('Y-m-d')),
    ];
}
