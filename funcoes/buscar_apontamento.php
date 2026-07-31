<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../funcoes/busca_dados.php';

header('Content-Type: application/json');

session_start();

$user_id = caderno_require_user_id();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || !$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'ID ou usuário inválido']);
    exit;
}

$detalhe = getApontamentoPorId($mysqli, (int)$user_id, $id);

if ($detalhe) {
    echo json_encode(['ok' => true, 'apontamento' => $detalhe]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Apontamento não encontrado']);
}
