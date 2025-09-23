<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$id = (int)($_POST['id'] ?? 0);
$redirect = isset($_POST['redirect']) ? true : false;

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$id || !$user_id) {
    if ($redirect) {
        header("Location: /paginas/minhas_propriedades.php?msg=erro");
        exit;
    }
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

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

try {
    $stmt = $mysqli->prepare("UPDATE propriedades 
        SET nome_razao=?, tipo_doc=?, cpf_cnpj=?, email=?, endereco_rua=?, endereco_numero=?, endereco_uf=?, endereco_cidade=?, telefone1=?, telefone2=? 
        WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssssssssii",
        $nome, $tipo, $doc, $email, $rua, $num, $uf, $cidade, $tel1, $tel2,
        $id, $user_id
    );
    $stmt->execute();

    if ($redirect) {
        header("Location: /paginas/minhas_propriedades.php?msg=ok");
        exit;
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    if ($redirect) {
        header("Location: /paginas/minhas_propriedades.php?msg=erro");
        exit;
    }
    echo json_encode(['ok' => false, 'err' => 'db', 'msg' => $e->getMessage()]);
}
