<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../configuracao/login_audit.php';

adminRequirePerfil($mysqli, ['admin']);
loginAuditEnsureSchema($mysqli);

$dias = max(1, min(90, (int) ($_GET['dias'] ?? 7)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$sucessoFiltro = (string) ($_GET['sucesso'] ?? '');
$motivoFiltro = trim((string) ($_GET['motivo'] ?? ''));
$loginFiltro = trim((string) ($_GET['login'] ?? ''));

$where = ['criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)'];
$params = [$dias];
$types = 'i';

if ($sucessoFiltro === '0' || $sucessoFiltro === '1') {
    $where[] = 'sucesso = ?';
    $params[] = (int) $sucessoFiltro;
    $types .= 'i';
}

if ($motivoFiltro !== '') {
    $where[] = 'motivo = ?';
    $params[] = substr($motivoFiltro, 0, 50);
    $types .= 's';
}

if ($loginFiltro !== '') {
    $where[] = 'login_hash = ?';
    $params[] = loginAuditHashLogin($loginFiltro);
    $types .= 's';
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) AS total FROM login_attempts WHERE {$whereSql}";
$stmtCount = $mysqli->prepare($countSql);
$stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

$listSql = "
    SELECT id, login_hash, ip_hash, sucesso, motivo, criado_em
    FROM login_attempts
    WHERE {$whereSql}
    ORDER BY criado_em DESC
    LIMIT ? OFFSET ?
";
$stmtList = $mysqli->prepare($listSql);
$listTypes = $types . 'ii';
$listParams = array_merge($params, [$limit, $offset]);
$stmtList->bind_param($listTypes, ...$listParams);
$stmtList->execute();
$tentativas = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

$statsSql = "
    SELECT
        SUM(sucesso = 1) AS sucessos,
        SUM(sucesso = 0) AS falhas,
        COUNT(*) AS total
    FROM login_attempts
    WHERE criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
";
$stmtStats = $mysqli->prepare($statsSql);
$stmtStats->bind_param('i', $dias);
$stmtStats->execute();
$resumo = $stmtStats->get_result()->fetch_assoc() ?: ['sucessos' => 0, 'falhas' => 0, 'total' => 0];
$stmtStats->close();

$motivosSql = "
    SELECT motivo, COUNT(*) AS total
    FROM login_attempts
    WHERE sucesso = 0
      AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY motivo
    ORDER BY total DESC
    LIMIT 10
";
$stmtMotivos = $mysqli->prepare($motivosSql);
$stmtMotivos->bind_param('i', $dias);
$stmtMotivos->execute();
$motivos = $stmtMotivos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMotivos->close();

$ipsSql = "
    SELECT ip_hash, COUNT(*) AS total
    FROM login_attempts
    WHERE sucesso = 0
      AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY ip_hash
    HAVING total >= 3
    ORDER BY total DESC
    LIMIT 10
";
$stmtIps = $mysqli->prepare($ipsSql);
$stmtIps->bind_param('i', $dias);
$stmtIps->execute();
$ipsSuspeitos = $stmtIps->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtIps->close();

adminJson([
    'ok' => true,
    'resumo' => [
        'total' => (int) ($resumo['total'] ?? 0),
        'sucessos' => (int) ($resumo['sucessos'] ?? 0),
        'falhas' => (int) ($resumo['falhas'] ?? 0),
    ],
    'motivos' => $motivos,
    'ips_suspeitos' => $ipsSuspeitos,
    'tentativas' => $tentativas,
    'paginacao' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => max(1, (int) ceil($total / $limit)),
    ],
]);
