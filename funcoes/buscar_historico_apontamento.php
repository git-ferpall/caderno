<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/apontamento_historico.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = (int)($payload['sub'] ?? 0);
    }
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('id_invalido');
    }

    garantirTabelaApontamentoHistorico($mysqli);

    $stmtCheck = $mysqli->prepare("
        SELECT a.id
        FROM apontamentos a
        JOIN propriedades p ON p.id = a.propriedade_id
        WHERE a.id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmtCheck->bind_param('ii', $id, $user_id);
    $stmtCheck->execute();
    if (!$stmtCheck->get_result()->fetch_assoc()) {
        throw new Exception('apontamento_nao_encontrado');
    }
    $stmtCheck->close();

    $stmt = $mysqli->prepare("
        SELECT campo, valor_anterior, valor_novo, alterado_em
        FROM apontamento_historico
        WHERE apontamento_id = ?
        ORDER BY alterado_em DESC, id DESC
        LIMIT 100
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $historico = array_map(function ($row) {
        return [
            'campo' => labelCampoApontamento($row['campo']),
            'valor_anterior' => $row['valor_anterior'] ?? '—',
            'valor_novo' => $row['valor_novo'] ?? '—',
            'alterado_em' => date('d/m/Y H:i', strtotime($row['alterado_em'])),
        ];
    }, $rows);

    echo json_encode(['ok' => true, 'historico' => $historico]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
