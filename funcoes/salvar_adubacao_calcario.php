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

$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa']);
    exit;
}

$propriedade_id = $prop['id'];

$data = $_POST['data'] ?? null;
$areas = $_POST['area'] ?? [];
$produto_id = (int)($_POST['produto'] ?? 0);
$tipo = $_POST['tipo'] ?? null;
$quantidade = $_POST['quantidade'] ?? null;
$prnt = $_POST['prnt'] ?? null;
$forma = $_POST['forma_aplicacao'] ?? null;
$nref = $_POST['n_referencia'] ?? null;
$obs = $_POST['obs'] ?? null;

if (!$data || empty($areas) || !$produto_id) {
    echo json_encode(['ok' => false, 'err' => 'Campos obrigatórios não preenchidos']);
    exit;
}

$mysqli->begin_transaction();

try {
    $tipo_apont = "adubacao_calcario";
    $status = "pendente";

    $stmt = $mysqli->prepare("
        INSERT INTO apontamentos 
        (propriedade_id, tipo, data, quantidade, observacoes, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdss", $propriedade_id, $tipo_apont, $data, $quantidade, $obs, $status);
    $stmt->execute();
    $apont_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $mysqli->prepare("INSERT INTO apontamento_detalhes (apontamento_id, campo, valor) VALUES (?, ?, ?)");

    foreach ($areas as $area_id) {
        $campo = "area_id";
        $valor = (int)$area_id;
        $stmt->bind_param("iss", $apont_id, $campo, $valor);
        $stmt->execute();
    }

    $detalhes = [
        ['produto_id', $produto_id],
        ['tipo_produto', $tipo],
        ['prnt', $prnt],
        ['forma_aplicacao', $forma],
        ['referencia_amostra', $nref]
    ];

    foreach ($detalhes as [$campo, $valor]) {
        $stmt->bind_param("iss", $apont_id, $campo, $valor);
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['ok' => true, 'msg' => 'Apontamento de adubação salvo com sucesso!']);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
?>
