<?php
require_once __DIR__ . '/../configuracao/conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';

header('Content-Type: application/json');

$data = $_POST['data'] ?? null;
$area = $_POST['area'] ?? null;
$produto = $_POST['produto'] ?? null;
$quantidade = $_POST['quantidade'] ?? null;
$previsao = $_POST['previsao'] ?? null;
$obs = $_POST['obs'] ?? null;

if (!$data || !$area || !$produto || !$quantidade) {
    echo json_encode(['status' => 'erro', 'msg' => 'Campos obrigatórios faltando']);
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO apontamento (tipo, data, area_id, produto_id, quantidade, previsao_colheita, observacoes, propriedade_id)
            VALUES ('plantio', ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data, $area, $produto, $quantidade, $previsao, $obs,
        $_SESSION['propriedade_id'] ?? null
    ]);

    $pdo->commit();

    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
}
