<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

$data        = $_POST['data'] ?? null;
$area_id     = $_POST['area'] ?? null;
$produto_id  = $_POST['produto'] ?? null;
$quantidade  = $_POST['quantidade'] ?? null;
$obs         = $_POST['obs'] ?? null;

$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma propriedade ativa encontrada.']);
    exit;
}

$propriedade_id = $prop['id'];
$status = !empty($quantidade) ? "concluido" : "pendente";

$tipo = "colheita";
$stmt = $mysqli->prepare("
    INSERT INTO apontamentos (propriedade_id, tipo, data, quantidade, observacoes, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("issdss", $propriedade_id, $tipo, $data, $quantidade, $obs, $status);

if (!$stmt->execute()) {
    echo json_encode(['ok' => false, 'msg' => "Erro: " . $stmt->error]);
    exit;
}

$apontamento_id = $stmt->insert_id;
$stmt->close();

$detalhes = [
    'area_id'    => $area_id,
    'produto_id' => $produto_id,
];

foreach ($detalhes as $campo => $valor) {
    if (!empty($valor)) {
        $stmt = $mysqli->prepare("
            INSERT INTO apontamento_detalhes (apontamento_id, campo, valor)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $apontamento_id, $campo, $valor);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode(['ok' => true, 'msg' => 'Apontamento de colheita salvo com sucesso!']);
