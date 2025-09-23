<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'err'=>'method_not_allowed']);
    exit;
}

try {
    // valida JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'err'=>'unauthorized']);
        exit;
    }

    // pega dados do formulário
    $nome   = trim($_POST['pfrazao'] ?? '');
    $tipo   = trim($_POST['pftipo'] ?? '');
    $cnpj   = trim($_POST['pfcnpj'] ?? '');
    $cpf    = trim($_POST['pfcpf'] ?? '');
    $email  = trim($_POST['pfemail-com'] ?? '');
    $rua    = trim($_POST['pfender-rua'] ?? '');
    $num    = trim($_POST['pfender-num'] ?? '');
    $uf     = trim($_POST['pfender-uf'] ?? '');
    $cidade = trim($_POST['pfender-cid'] ?? '');
    $tel1   = trim($_POST['pfnum1-com'] ?? '');
    $tel2   = trim($_POST['pfnum2-com'] ?? '');

    $doc = ($tipo === 'cpf') ? $cpf : $cnpj;

    // desativa propriedades antigas
    $stmt = $mysqli->prepare("UPDATE propriedades SET ativo=0 WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // insere nova propriedade como ativa
    $stmt = $mysqli->prepare("
        INSERT INTO propriedades 
        (user_id, nome_razao, tipo_doc, cpf_cnpj, email, endereco_rua, endereco_numero, endereco_uf, endereco_cidade, telefone1, telefone2, ativo, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->bind_param("issssssssss", $user_id, $nome, $tipo, $doc, $email, $rua, $num, $uf, $cidade, $tel1, $tel2);
    $stmt->execute();

    $newId = $stmt->insert_id;

    // decide se retorna JSON ou redireciona
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json') || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['ok'=>true,'id'=>$newId]);
    } else {
        header("Location: /home/propriedade.php?sucesso=1");

        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'db','msg'=>$e->getMessage()]);
}
