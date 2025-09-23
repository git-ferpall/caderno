<?php
// funcoes/salvar_propriedade.php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php'; // valida JWT e retorna payload

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

// Captura dados do usuário via JWT (cookie ou Authorization)
$payload = verify_jwt();
$user_id = $payload['sub'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

// Sanitiza dados do POST
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

// Documento correto (dependendo se é CPF ou CNPJ)
$doc = ($tipo === 'cpf') ? $cpf : $cnpj;

try {
    // Desativa propriedades anteriores do usuário
    $stmt = $mysqli->prepare("UPDATE propriedades SET ativo = 0 WHERE usuario_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Insere a nova como ativa
    $stmt = $mysqli->prepare("
        INSERT INTO propriedades 
        (usuario_id, nome_razao, tipo_doc, cpf_cnpj, email, rua, numero, uf, cidade, telefone1, telefone2, ativo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("issssssssss", $user_id, $nome, $tipo, $doc, $email, $rua, $num, $uf, $cidade, $tel1, $tel2);
    $stmt->execute();

    echo json_encode(['ok' => true, 'id' => $stmt->insert_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'db', 'msg' => $e->getMessage()]);
}
