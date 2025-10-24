<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// Exibir erros temporariamente (para debug, pode remover depois)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Identifica o usuário autenticado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// 🔹 Consulta estufas + áreas vinculadas (bancadas)
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
WHERE e.user_id = ?
ORDER BY e.id ASC
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'err' => 'Erro na preparação da query: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$estufas = [];
while ($row = $res->fetch_assoc()) {
    $eid = $row['estufa_id'];
    $aid = $row['area_id'];

    // Cria a estufa se ainda não foi adicionada
    if (!isset($estufas[$eid])) {
        $estufas[$eid] = [
            'id' => $eid,
            'nome' => $row['estufa_nome'],
            'area' => $row['area_m2'],
            'obs' => $row['estufa_obs'],
            'bancadas' => []
        ];
    }

    // Adiciona as áreas vinculadas (bancadas)
    if ($aid) {
        $estufas[$eid]['bancadas'][] = [
            'id' => $aid,
            'nome' => $row['area_nome'],
            'tipo' => $row['area_tipo']
        ];
    }
}

echo json_encode([
    'ok' => true,
    'estufas' => array_values($estufas)
]);
