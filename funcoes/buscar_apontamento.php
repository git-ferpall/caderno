<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../funcoes/busca_dados.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$id = $_POST['id'] ?? null;
if (!$id || !$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'ID ou usuário inválido']);
    exit;
}

$apontamentos = getApontamentosCompletos($mysqli, $user_id);

// procura apenas o registro correspondente
$detalhe = null;
foreach ($apontamentos as $ap) {
    if ($ap['id'] == $id) {
        $detalhe = $ap;
        break;
    }
}

if ($detalhe) {
    echo json_encode(['ok' => true, 'apontamento' => $detalhe]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Apontamento não encontrado']);
}
