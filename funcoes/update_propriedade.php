<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/auth_user.php'; // agora temos $USER_ID disponível

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

// Update seguro
$stmt = $mysqli->prepare("
    UPDATE propriedades
       SET nome_razao = ?, tipo_doc = ?, cpf_cnpj = ?, email = ?,
           endereco_rua = ?, endereco_numero = ?, endereco_uf = ?, endereco_cidade = ?,
           telefone1 = ?, telefone2 = ?
     WHERE id = ? AND user_id = ?
");
$stmt->bind_param(
    "ssssssssssii",
    $nome, $tipo, $cpf_cnpj, $email,
    $rua, $num, $uf, $cid,
    $tel1, $tel2,
    $id, $USER_ID
);

$ok = $stmt->execute();

echo json_encode([
    "ok" => $ok,
    "updated" => $stmt->affected_rows
]);

// Se veio do formulário normal (não AJAX), redireciona de volta
if (!empty($_POST['redirect'])) {
    header("Location: /home/minhas_propriedades.php");
    exit;
}
