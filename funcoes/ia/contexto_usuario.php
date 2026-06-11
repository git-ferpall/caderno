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

    require_once __DIR__ . '/consultas.php';
    $resumo_rapido = iaResumoRapidoPropriedade($mysqli, $propriedade_id);

    return [
        'propriedade' => ['id' => $propriedade_id, 'nome' => $prop['nome_razao'] ?? ''],
        'areas' => $areas,
        'produtos' => $produtos,
        'herbicidas' => iaCarregarCatalogo($mysqli, 'herbicidas', 'ativo'),
        'fungicidas' => iaCarregarCatalogo($mysqli, 'fungicidas', 'ativo'),
        'inseticidas' => iaCarregarCatalogo($mysqli, 'inseticidas', 'ativo'),
        'fertilizantes' => iaCarregarCatalogo($mysqli, 'fertilizantes', 'status'),
        'pendentes' => $pendentes,
        'resumo_rapido' => $resumo_rapido,
        'tipos_manejo' => iaTiposManejo(),
        'hoje' => date('Y-m-d'),
    ];
}

function iaCarregarCatalogo(mysqli $mysqli, string $tabela, string $colStatus): array
{
    $permitidas = ['herbicidas', 'fungicidas', 'inseticidas', 'fertilizantes'];
    if (!in_array($tabela, $permitidas, true)) {
        return [];
    }

    $sql = match ($tabela) {
        'herbicidas' => "SELECT id, nome FROM herbicidas WHERE status = 'ativo' ORDER BY nome ASC",
        'fertilizantes' => "SELECT id, nome FROM fertilizantes WHERE status = 'ativo' ORDER BY nome ASC",
        default => "SELECT id, nome FROM {$tabela} WHERE ativo = 1 ORDER BY nome ASC",
    };

    $res = $mysqli->query($sql);
    if (!$res) {
        return [];
    }

    $itens = [];
    while ($row = $res->fetch_assoc()) {
        $itens[] = ['id' => (int) $row['id'], 'nome' => (string) $row['nome']];
    }
    return $itens;
}

function iaTiposManejo(): array
{
    return [
        'irrigacao' => 'Irrigação',
        'colheita' => 'Colheita',
        'semeadura' => 'Semeadura',
        'plantio' => 'Plantio',
        'herbicida' => 'Herbicida',
        'fungicida' => 'Fungicida',
        'inseticida' => 'Inseticida',
        'fertilizante' => 'Fertilizante',
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
            a.quantidade,
            a.unidade,
            a.observacoes,
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
            'quantidade' => $row['quantidade'] ?? null,
            'unidade' => $row['unidade'] ?? '',
            'observacoes' => $row['observacoes'] ?? '',
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
        'herbicidas' => array_slice(array_column($contexto['herbicidas'] ?? [], 'nome'), 0, 25),
        'fungicidas' => array_slice(array_column($contexto['fungicidas'] ?? [], 'nome'), 0, 25),
        'hoje' => (string) ($contexto['hoje'] ?? date('Y-m-d')),
        'resumo' => array_merge(
            ['pendentes' => count($contexto['pendentes'] ?? [])],
            ($contexto['resumo_rapido'] ?? [])
        ),
    ];
}
