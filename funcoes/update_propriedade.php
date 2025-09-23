<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// tenta pegar via sessão
$user_id = $_SESSION['user_id'] ?? null;

// se não houver sessão, tenta via JWT
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    die(json_encode(["ok" => false, "error" => "Usuário não autenticado"]));
}

header('Content-Type: application/json; charset=utf-8');

// Garantir que veio via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["ok" => false, "error" => "Método inválido"]);
    exit;
}

$id       = intval($_POST['id'] ?? 0);
$nome     = trim($_POST['pfrazao'] ?? '');
$tipo     = $_POST['pftipo'] ?? '';
$cpf_cnpj = $_POST['pfcnpj'] ?? $_POST['pfcpf'] ?? '';
$email    = trim($_POST['pfemail-com'] ?? '');
$rua      = trim($_POST['pfender-rua'] ?? '');
$num      = trim($_POST['pfender-num'] ?? '');
$uf       = trim($_POST['pfender-uf'] ?? '');
$cid      = trim($_POST['pfender-cid'] ?? '');
$tel1     = trim($_POST['pfnum1-com'] ?? '');
$tel2     = trim($_POST['pfnum2-com'] ?? '');

if ($id <= 0 || empty($nome)) {
    echo json_encode(["ok" => false, "error" => "Dados inválidos"]);
    exit;
}

// Faz o UPDATE
$stmt = $mysqli->prepare("UPDATE propriedades 
    SET nome_razao=?, tipo_doc=?, cpf_cnpj=?, email=?, 
        endereco_rua=?, endereco_numero=?, endereco_uf=?, endereco_cidade=?, 
        telefone1=?, telefone2=? 
    WHERE id=? AND user_id=?");
$stmt->bind_param(
    "ssssssssssii",
    $nome, $tipo, $cpf_cnpj, $email,
    $rua, $num, $uf, $cid,
    $tel1, $tel2,
    $id, $user_id
);
$ok = $stmt->execute();
$updated = $stmt->affected_rows;
$stmt->close();

// Se veio do form normal (redirect=1), redireciona
if (!empty($_POST['redirect'])) {
    if ($ok) {
        header("Location: /home/minhas_propriedades.php?sucesso=1");
    } else {
        header("Location: /home/minhas_propriedades.php?erro=1");
    }
    exit;
}

// Caso contrário, responde JSON (útil para AJAX/teste com curl)
echo json_encode([
    "ok"      => $ok,
    "updated" => $updated,
    "error"   => $ok ? null : $mysqli->error
]);
