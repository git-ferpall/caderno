<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

$sql = "
SELECT 
    e.id AS estufa_id, e.nome AS estufa_nome, e.area_m2, e.observacoes AS estufa_obs,
    a.id AS area_id, a.nome AS area_nome, a.tipo, a.observacoes AS area_obs
FROM estufas e
LEFT JOIN estufa_areas ea ON ea.estufa_id = e.id
LEFT JOIN areas a ON a.id = ea.area_id
WHERE e.user_id = ?
ORDER BY e.id ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$estufas = [];
while ($row = $res->fetch_assoc()) {
    $eid = $row['estufa_id'];
    $aid = $row['area_id'];

    if (!isset($estufas[$eid])) {
        $estufas[$eid] = [
            'id' => $eid,
            'nome' => $row['estufa_nome'],
            'area' => $row['area_m2'],
            'obs' => $row['estufa_obs'],
            'bancadas' => []
        ];
    }

    if ($aid) {
        $estufas[$eid]['bancadas'][] = [
            'id' => $aid,
            'nome' => $row['area_nome'],
            'tipo' => $row['tipo'],
            'obs' => $row['area_obs']
        ];
    }
}

echo json_encode(['ok' => true, 'estufas' => array_values($estufas)]);
