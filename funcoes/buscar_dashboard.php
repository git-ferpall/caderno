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

$propriedade_id = (int)$prop['id'];
$hoje = date('Y-m-d');
$inicioMes = date('Y-m-01');
$domingo = date('Y-m-d', strtotime('sunday this week'));

try {
    $stmt = $mysqli->prepare("
        SELECT
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
            SUM(CASE WHEN status = 'pendente' AND data < ? THEN 1 ELSE 0 END) AS atrasados,
            SUM(CASE WHEN status = 'pendente' AND data <= ? THEN 1 ELSE 0 END) AS semana,
            SUM(CASE WHEN status = 'concluido' AND DATE(COALESCE(data_conclusao, data)) >= ? THEN 1 ELSE 0 END) AS concluidos_mes
        FROM apontamentos
        WHERE propriedade_id = ?
    ");
    $stmt->bind_param('sssi', $hoje, $domingo, $inicioMes, $propriedade_id);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $stmt = $mysqli->prepare("
        SELECT DATE(COALESCE(data_conclusao, data)) AS dt
        FROM apontamentos
        WHERE propriedade_id = ? AND tipo = 'irrigacao'
        ORDER BY COALESCE(data_conclusao, data) DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $propriedade_id);
    $stmt->execute();
    $ultIrrig = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $mysqli->prepare("
        SELECT tipo, DATE(COALESCE(data_conclusao, data)) AS dt
        FROM apontamentos
        WHERE propriedade_id = ?
          AND tipo IN ('herbicida','fungicida','inseticida','fertilizante','defensivo','Defensivo')
        ORDER BY COALESCE(data_conclusao, data) DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $propriedade_id);
    $stmt->execute();
    $ultApp = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $limite_mb = 1024;
    $stmt = $mysqli->prepare('SELECT limite_mb FROM silo_limites WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $limRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (isset($limRow['limite_mb'])) {
        $limite_mb = (int)$limRow['limite_mb'];
    }

    $stmt = $mysqli->prepare('SELECT COALESCE(SUM(tamanho_bytes), 0) AS total FROM silo_arquivos WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $usado_bytes = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $limite_bytes = $limite_mb * 1024 * 1024;
    $silo_percent = $limite_bytes > 0 ? min(100, round(($usado_bytes / $limite_bytes) * 100, 1)) : 0;

    $inicio30 = date('Y-m-d', strtotime('-30 days'));
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS total
        FROM apontamentos
        WHERE propriedade_id = ?
          AND DATE(COALESCE(data_conclusao, data)) >= ?
    ");
    $stmt->bind_param('is', $propriedade_id, $inicio30);
    $stmt->execute();
    $eventos30 = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'propriedade' => $prop['nome_razao'],
        'pendentes' => (int)($counts['pendentes'] ?? 0),
        'atrasados' => (int)($counts['atrasados'] ?? 0),
        'semana' => (int)($counts['semana'] ?? 0),
        'concluidos_mes' => (int)($counts['concluidos_mes'] ?? 0),
        'eventos_30_dias' => $eventos30,
        'ultima_irrigacao' => $ultIrrig['dt'] ?? null,
        'ultima_aplicacao' => $ultApp ? [
            'tipo' => labelTipoApontamento($ultApp['tipo']),
            'data' => $ultApp['dt'],
        ] : null,
        'silo' => [
            'usado_bytes' => $usado_bytes,
            'limite_bytes' => $limite_bytes,
            'percentual' => $silo_percent,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao carregar resumo']);
}
