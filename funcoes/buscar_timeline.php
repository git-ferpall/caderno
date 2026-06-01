<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/apontamento_arquivos.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int)($payload['sub'] ?? 0);
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

$prop = obterPropriedadeAtiva($mysqli, $user_id);
if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa']);
    exit;
}

garantirTabelaApontamentoArquivos($mysqli);

$propriedade_id = (int)$prop['id'];
$limite = max(5, min(50, (int)($_GET['limite'] ?? 20)));
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $limite;

$data_ini = trim($_GET['data_ini'] ?? '');
$data_fim = trim($_GET['data_fim'] ?? '');
$area_id = (int)($_GET['area_id'] ?? 0);
$tipo = trim($_GET['tipo'] ?? '');
$status = trim($_GET['status'] ?? '');

if ($data_ini === '') {
    $data_ini = date('Y-m-d', strtotime('-90 days'));
}
if ($data_fim === '') {
    $data_fim = date('Y-m-d');
}

$where = ['a.propriedade_id = ?', 'DATE(COALESCE(a.data_conclusao, a.data)) BETWEEN ? AND ?'];
$types = 'iss';
$params = [$propriedade_id, $data_ini, $data_fim];

if ($area_id > 0) {
    $where[] = "EXISTS (
        SELECT 1 FROM apontamento_detalhes ad
        WHERE ad.apontamento_id = a.id AND ad.campo = 'area_id' AND ad.valor = ?
    )";
    $types .= 's';
    $params[] = (string)$area_id;
}

if ($tipo !== '') {
    $where[] = 'LOWER(a.tipo) = LOWER(?)';
    $types .= 's';
    $params[] = $tipo;
}

if ($status !== '' && in_array($status, ['pendente', 'concluido', 'registro'], true)) {
    $where[] = 'a.status = ?';
    $types .= 's';
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);

$sqlCount = "
    SELECT COUNT(DISTINCT a.id) AS total
    FROM apontamentos a
    WHERE $whereSql
";
$stmt = $mysqli->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$sql = "
    SELECT
        a.id,
        a.tipo,
        a.status,
        a.data,
        a.data_conclusao,
        DATE(COALESCE(a.data_conclusao, a.data)) AS evento_em,
        a.quantidade,
        a.unidade,
        a.observacoes,
        (
            SELECT GROUP_CONCAT(DISTINCT ar.nome ORDER BY ar.nome SEPARATOR ', ')
            FROM apontamento_detalhes ad
            INNER JOIN areas ar ON ar.id = ad.valor
            WHERE ad.apontamento_id = a.id AND ad.campo = 'area_id'
        ) AS areas,
        (
            SELECT GROUP_CONCAT(DISTINCT COALESCE(p.nome, ad.valor) ORDER BY 1 SEPARATOR ', ')
            FROM apontamento_detalhes ad
            LEFT JOIN produtos p ON p.id = ad.valor AND ad.campo IN ('produto_id', 'produto')
            WHERE ad.apontamento_id = a.id AND ad.campo IN ('produto_id', 'produto')
        ) AS produtos,
        (
            SELECT COUNT(*) FROM apontamento_arquivos aa WHERE aa.apontamento_id = a.id
        ) AS anexos
    FROM apontamentos a
    WHERE $whereSql
    ORDER BY evento_em DESC, a.id DESC
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($sql);
$typesLimit = $types . 'ii';
$paramsLimit = array_merge($params, [$limite, $offset]);
$stmt->bind_param($typesLimit, ...$paramsLimit);
$stmt->execute();
$res = $stmt->get_result();

$eventos = [];
while ($row = $res->fetch_assoc()) {
    $tipoRaw = (string)$row['tipo'];
    $eventos[] = [
        'fonte' => 'apontamento',
        'id' => (int)$row['id'],
        'tipo' => $tipoRaw,
        'tipo_label' => labelTipoApontamento($tipoRaw),
        'icone' => iconeTipoApontamento($tipoRaw),
        'status' => $row['status'],
        'evento_em' => $row['evento_em'],
        'data' => $row['data'],
        'data_conclusao' => $row['data_conclusao'],
        'areas' => $row['areas'] ?: '',
        'produtos' => $row['produtos'] ?: '',
        'quantidade' => $row['quantidade'],
        'unidade' => $row['unidade'],
        'observacoes' => $row['observacoes'],
        'anexos' => (int)$row['anexos'],
    ];
}
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT id, nome_arquivo, criado_em
    FROM silo_arquivos
    WHERE user_id = ? AND tipo = 'arquivo'
      AND DATE(criado_em) BETWEEN ? AND ?
    ORDER BY criado_em DESC
    LIMIT 10
");
$stmt->bind_param('iss', $user_id, $data_ini, $data_fim);
$stmt->execute();
$siloRes = $stmt->get_result();
while ($s = $siloRes->fetch_assoc()) {
    $eventos[] = [
        'fonte' => 'silo',
        'id' => (int)$s['id'],
        'tipo' => 'arquivo',
        'tipo_label' => 'Arquivo no Silo',
        'icone' => '📎',
        'status' => 'arquivo',
        'evento_em' => date('Y-m-d', strtotime($s['criado_em'])),
        'areas' => '',
        'produtos' => $s['nome_arquivo'],
        'quantidade' => null,
        'unidade' => null,
        'observacoes' => '',
        'anexos' => 0,
    ];
}
$stmt->close();

usort($eventos, fn($a, $b) => strcmp($b['evento_em'], $a['evento_em']));

$total_paginas = max(1, (int)ceil($total / $limite));

echo json_encode([
    'ok' => true,
    'eventos' => $eventos,
    'total' => $total,
    'pagina' => $pagina,
    'total_paginas' => $total_paginas,
    'filtros' => [
        'data_ini' => $data_ini,
        'data_fim' => $data_fim,
    ],
], JSON_UNESCAPED_UNICODE);
