<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // 🔹 Identifica o usuário via sessão ou JWT
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        echo json_encode(['ok' => false, 'err' => 'usuario_nao_autenticado']);
        exit;
    }

    // 🔹 Busca propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propriedade = $res->fetch_assoc();
    $stmt->close();

    if (!$propriedade) {
        echo json_encode(['ok' => false, 'err' => 'nenhuma_propriedade_ativa']);
        exit;
    }

    $prop_id = (int)$propriedade['id'];

    // 🔹 Busca estufas com bancadas (áreas vinculadas)
    $sql = "
        SELECT 
            e.id AS estufa_id,
            e.nome AS estufa_nome,
            e.area_m2,
            e.observacoes AS estufa_obs,
            a.id AS area_id,
            a.nome AS area_nome,
            a.tipo AS area_tipo
        FROM estufas e
        LEFT JOIN estufa_areas ea ON ea.estufa_id = e.id
        LEFT JOIN areas a ON a.id = ea.area_id
        WHERE e.user_id = ? AND e.propriedade_id = ?
        ORDER BY e.id ASC
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $mysqli->error);
    }

    $stmt->bind_param('ii', $user_id, $prop_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $estufas = [];
    while ($row = $res->fetch_assoc()) {
        $eid = $row['estufa_id'];
        $aid = $row['area_id'];

        // Adiciona a estufa caso ainda não esteja no array
        if (!isset($estufas[$eid])) {
            $estufas[$eid] = [
                'id' => (int)$eid,
                'nome' => $row['estufa_nome'],
                'area' => $row['area_m2'] ?: '',
                'obs' => $row['estufa_obs'] ?: '',
                'bancadas' => []
            ];
        }

        // Adiciona bancadas (áreas vinculadas)
        if ($aid) {
            $estufas[$eid]['bancadas'][] = [
                'id' => (int)$aid,
                'nome' => $row['area_nome'],
                'tipo' => $row['area_tipo']
            ];
        }
    }

    echo json_encode([
        'ok' => true,
        'estufas' => array_values($estufas)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'erro_interno',
        'msg' => $e->getMessage()
    ]);
}
