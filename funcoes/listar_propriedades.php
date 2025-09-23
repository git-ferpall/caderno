<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false, 'err'=>'unauthorized']);
        exit;
    }

    $stmt = $mysqli->prepare("
        SELECT id, nome_razao, tipo_doc, cpf_cnpj, email, 
               endereco_rua, endereco_numero, endereco_uf, endereco_cidade, 
               telefone1, telefone2, ativo, created_at
        FROM propriedades
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $propriedades = [];
    while ($row = $res->fetch_assoc()) {
        $propriedades[] = $row;
    }

    echo json_encode(['ok'=>true, 'propriedades'=>$propriedades]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'err'=>'db', 'msg'=>$e->getMessage()]);
}
