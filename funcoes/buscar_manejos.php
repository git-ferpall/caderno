<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

// === Propriedade ativa ===
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa encontrada']);
    exit;
}

$propriedade_id = $prop['id'];

// === Paginação ===
$limite = intval($_GET['limite'] ?? 10);
if ($limite <= 0) $limite = 10;

$paginaPendente = intval($_GET['pendente_page'] ?? 1);
if ($paginaPendente <= 0) $paginaPendente = 1;

$paginaConcluido = intval($_GET['concluido_page'] ?? 1);
if ($paginaConcluido <= 0) $paginaConcluido = 1;

$offsetPendente = ($paginaPendente - 1) * $limite;
$offsetConcluido = ($paginaConcluido - 1) * $limite;

// === Ordenação (para ficar global com paginação) ===
$pendente_sort = $_GET['pendente_sort'] ?? 'data';
$concluido_sort = $_GET['concluido_sort'] ?? 'data';

$pendente_dir = strtolower($_GET['pendente_dir'] ?? 'desc');
if (!in_array($pendente_dir, ['asc', 'desc'])) $pendente_dir = 'desc';

$concluido_dir = strtolower($_GET['concluido_dir'] ?? 'desc');
if (!in_array($concluido_dir, ['asc', 'desc'])) $concluido_dir = 'desc';

function montarOrderBy($sort, $dir)
{
    $dirSql = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
    $sort = (string)$sort;

    // mapear somente chaves conhecidas para evitar SQL injection
    switch ($sort) {
        case 'data':
            $expr = 'a.data';
            break;
        case 'conclusao':
            $expr = 'CASE WHEN a.data_conclusao IS NULL THEN 1 ELSE 0 END, a.data_conclusao';
            break;
        case 'tipo':
            $expr = 'a.tipo';
            break;
        case 'areas':
            $expr = 'areas';
            break;
        case 'produto':
            $expr = 'produto_nome';
            break;
        default:
            $expr = 'a.data';
            break;
    }

    return $expr . ' ' . $dirSql;
}

function buscarPorStatus($mysqli, $propriedade_id, $status, $limite, $offset, $sort, $dir)
{
    $sql = "
        SELECT 
            a.id,
            a.tipo,
            a.data,
            a.data_conclusao,
            a.status,
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
        LEFT JOIN apontamento_detalhes ad 
            ON ad.apontamento_id = a.id AND ad.campo = 'area_id'
        LEFT JOIN areas ar 
            ON ar.id = ad.valor
        WHERE a.propriedade_id = ?
          AND a.status = ?
        GROUP BY a.id
        ORDER BY ___ORDERBY___
        LIMIT ? OFFSET ?
    ";
    $orderBy = montarOrderBy($sort, $dir);

    // Inserção controlada: montagem via whitelist (montarOrderBy)
    $sqlFinal = str_replace("___ORDERBY___", $orderBy, $sql);
    $stmt = $mysqli->prepare($sqlFinal);
    $stmt->bind_param("isii", $propriedade_id, $status, $limite, $offset);
    $stmt->execute();
    $res = $stmt->get_result();

    $itens = [];
    while ($row = $res->fetch_assoc()) {
        $itens[] = [
            'id' => (int)$row['id'],
            'tipo' => ucfirst(str_replace('_', ' ', $row['tipo'])),
            'data' => date('d/m/Y', strtotime($row['data'])),
            'conclusao' => ($row['status'] === 'concluido' && !empty($row['data_conclusao']))
                ? date('d/m/Y', strtotime($row['data_conclusao']))
                : null,
            'areas' => $row['areas'] ?: '—',
            'produto' => $row['produto_nome'] ?: '—',
            'status' => $row['status']
        ];
    }
    $stmt->close();

    return $itens;
}

function contarPorStatus($mysqli, $propriedade_id, $status)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM apontamentos WHERE propriedade_id = ? AND status = ?");
    $stmt->bind_param("is", $propriedade_id, $status);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

$totalPendente = contarPorStatus($mysqli, $propriedade_id, 'pendente');
$totalConcluido = contarPorStatus($mysqli, $propriedade_id, 'concluido');

$totalPaginasPendente = $limite > 0 ? (int)ceil($totalPendente / $limite) : 1;
$totalPaginasConcluido = $limite > 0 ? (int)ceil($totalConcluido / $limite) : 1;

// Ajusta página caso esteja fora do range
if ($paginaPendente > $totalPaginasPendente) $paginaPendente = max(1, $totalPaginasPendente);
if ($paginaConcluido > $totalPaginasConcluido) $paginaConcluido = max(1, $totalPaginasConcluido);

$pendentes = buscarPorStatus(
    $mysqli,
    $propriedade_id,
    'pendente',
    $limite,
    $offsetPendente,
    $pendente_sort,
    $pendente_dir
);
$concluidos = buscarPorStatus(
    $mysqli,
    $propriedade_id,
    'concluido',
    $limite,
    $offsetConcluido,
    $concluido_sort,
    $concluido_dir
);

echo json_encode([
    'ok' => true,
    'pendentes'  => $pendentes,
    'concluidos' => $concluidos,
    'limite' => $limite,
    'pagina_pendente' => $paginaPendente,
    'pagina_concluido' => $paginaConcluido,
    'total_pendentes' => $totalPendente,
    'total_concluidos' => $totalConcluido,
    'total_paginas_pendente' => $totalPaginasPendente,
    'total_paginas_concluido' => $totalPaginasConcluido
]);
