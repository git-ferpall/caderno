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

// Busca propriedade ativa
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

// Busca todos os apontamentos da propriedade
$sql = "
    SELECT a.id, a.tipo, a.data, a.status, a.observacoes,
           GROUP_CONCAT(DISTINCT ad.valor SEPARATOR ', ') AS areas
      FROM apontamentos a
 LEFT JOIN apontamento_detalhes ad ON ad.apontamento_id = a.id AND ad.campo = 'area_id'
     WHERE a.propriedade_id = ?
  GROUP BY a.id
  ORDER BY a.data DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $propriedade_id);
$stmt->execute();
$res = $stmt->get_result();

$pendentes = [];
$concluidos = [];

while ($row = $res->fetch_assoc()) {
    $item = [
        'id'     => $row['id'],
        'tipo'   => ucfirst(str_replace('_', ' ', $row['tipo'])),
        'data'   => date('d/m/Y', strtotime($row['data'])),
        'areas'  => $row['areas'] ?: '—',
        'status' => $row['status']
    ];

    if ($row['status'] === 'pendente') {
        $pendentes[] = $item;
    } else {
        $concluidos[] = $item;
    }
}

echo json_encode([
    'ok' => true,
    'pendentes' => $pendentes,
    'concluidos' => $concluidos
]);
