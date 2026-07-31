<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = caderno_require_user_id();
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'err' => 'dados_invalidos']);
    exit;
}

$item_id = (int) ($data['id'] ?? 0);
if ($item_id <= 0) {
    echo json_encode(['ok' => false, 'err' => 'id_invalido']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT i.id
    FROM checklist_itens i
    INNER JOIN checklists c ON c.id = i.checklist_id
    WHERE i.id = ? AND c.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $item_id, $user_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    http_response_code(403);
    echo json_encode(['ok' => false, 'err' => 'sem_permissao']);
    exit;
}
$stmt->close();

$campos = [];
$tipos = '';
$valores = [];

if (array_key_exists('concluido', $data)) {
    $concluido = (int) ((bool) $data['concluido']);
    $campos[] = 'concluido = ?';
    $tipos .= 'i';
    $valores[] = $concluido;
    if ($concluido) {
        $campos[] = 'data_conclusao = ?';
        $tipos .= 's';
        $valores[] = date('Y-m-d H:i:s');
    } else {
        $campos[] = 'data_conclusao = NULL';
    }
}

if (array_key_exists('observacao', $data)) {
    $campos[] = 'observacao = ?';
    $tipos .= 's';
    $valores[] = (string) $data['observacao'];
}

if ($campos === []) {
    echo json_encode(['ok' => false, 'err' => 'nada_para_atualizar']);
    exit;
}

$sql = 'UPDATE checklist_itens SET ' . implode(', ', $campos) . ' WHERE id = ?';
$tipos .= 'i';
$valores[] = $item_id;

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true]);
