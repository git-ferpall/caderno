<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/apontamento_historico.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('method_not_allowed');
    }

    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = (int)($payload['sub'] ?? 0);
    }
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('id_invalido');
    }

    garantirTabelaApontamentoHistorico($mysqli);

    $stmt = $mysqli->prepare("
        SELECT a.id, a.data, a.observacoes, a.quantidade, a.unidade, a.tipo
        FROM apontamentos a
        JOIN propriedades p ON p.id = a.propriedade_id
        WHERE a.id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$atual) {
        throw new Exception('apontamento_nao_encontrado');
    }

    $novaData = array_key_exists('data', $_POST) ? trim((string)$_POST['data']) : null;
    $novaObs = array_key_exists('observacoes', $_POST) ? trim((string)$_POST['observacoes']) : null;
    $novaQtd = array_key_exists('quantidade', $_POST) ? trim((string)$_POST['quantidade']) : null;
    $novaUnidade = array_key_exists('unidade', $_POST) ? trim((string)$_POST['unidade']) : null;

    $tiposComQuantidade = ['colheita', 'irrigacao', 'fertilizante', 'herbicida', 'fungicida', 'inseticida'];
    $permiteQuantidade = in_array($atual['tipo'], $tiposComQuantidade, true)
        || $atual['quantidade'] !== null;

    $updates = [];
    $params = [];
    $types = '';

    if ($novaData !== null && $novaData !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $novaData)) {
            throw new Exception('data_invalida');
        }
        registrarHistoricoApontamento($mysqli, $id, $user_id, 'data', $atual['data'], $novaData);
        $updates[] = 'data = ?';
        $params[] = $novaData;
        $types .= 's';
    }

    if ($novaObs !== null) {
        registrarHistoricoApontamento($mysqli, $id, $user_id, 'observacoes', $atual['observacoes'], $novaObs);
        $updates[] = 'observacoes = ?';
        $params[] = $novaObs;
        $types .= 's';
    }

    if ($permiteQuantidade && $novaQtd !== null && $novaQtd !== '') {
        if (!is_numeric($novaQtd)) {
            throw new Exception('quantidade_invalida');
        }
        $qtdFloat = (float)$novaQtd;
        registrarHistoricoApontamento($mysqli, $id, $user_id, 'quantidade', $atual['quantidade'], $qtdFloat);
        $updates[] = 'quantidade = ?';
        $params[] = $qtdFloat;
        $types .= 'd';
    }

    if ($permiteQuantidade && $novaUnidade !== null) {
        registrarHistoricoApontamento($mysqli, $id, $user_id, 'unidade', $atual['unidade'], $novaUnidade);
        $updates[] = 'unidade = ?';
        $params[] = $novaUnidade;
        $types .= 's';
    }

    if (empty($updates)) {
        echo json_encode(['ok' => true, 'msg' => 'Nenhuma alteração detectada.']);
        exit;
    }

    $sql = 'UPDATE apontamentos SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $params[] = $id;
    $types .= 'i';

    $stmtUp = $mysqli->prepare($sql);
    $stmtUp->bind_param($types, ...$params);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento atualizado com sucesso.']);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
