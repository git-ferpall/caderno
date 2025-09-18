<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';

session_start();

if (!isset($_SESSION['cliente_cod'])) {
    http_response_code(403);
    echo "Sessão expirada ou não autenticado.";
    exit;
}

$cod_usuario = $_SESSION['cliente_cod'];

// Pega os campos do POST
$nome        = trim($_POST['pfrazao'] ?? '');
$cnpj        = trim($_POST['pfcnpj'] ?? '');
$email       = trim($_POST['pfemail-com'] ?? '');
$endereco    = trim($_POST['pfender-rua'] ?? '') . ', ' . trim($_POST['pfender-num'] ?? '');
$uf_sigla    = trim($_POST['pfender-uf'] ?? '');
$cidade_nome = trim($_POST['pfender-cid'] ?? '');
$tel1        = trim($_POST['pfnum1-com'] ?? '');
$tel2        = trim($_POST['pfnum2-com'] ?? '');

// (Opcional) Valida se todos os campos obrigatórios foram preenchidos
if (
    empty($nome) || empty($cnpj) || empty($email) || empty($endereco)
    || empty($uf_sigla) || empty($cidade_nome)
) {
    http_response_code(400);
    echo "Preencha todos os campos obrigatórios.";
    exit;
}

// Buscar o código da UF
$sqlUf = "SELECT codigo FROM uf WHERE sigla = ?";
$stmtUf = $mysqli->prepare($sqlUf);
if (!$stmtUf) {
    die("Erro ao preparar statement UF: " . $mysqli->error . " | SQL: $sqlUf");
}
$stmtUf->bind_param('s', $uf_sigla);
$stmtUf->execute();
$stmtUf->bind_result($uf_codigo);
$stmtUf->fetch();
$stmtUf->close();

if (empty($uf_codigo)) {
    http_response_code(400);
    echo "UF inválida.";
    exit;
}


// Buscar o código da cidade (AJUSTE AQUI)
$sqlCid = "SELECT cid_cod FROM cidade WHERE cid_nome = ? AND cid_cod_uf = ?";
$stmtCid = $mysqli->prepare($sqlCid);
if (!$stmtCid) {
    die("Erro ao preparar statement CIDADE: " . $mysqli->error . " | SQL: $sqlCid");
}
$stmtCid->bind_param('si', $cidade_nome, $uf_codigo);
$stmtCid->execute();
$stmtCid->bind_result($cidade_cid_cod);
$stmtCid->fetch();
$stmtCid->close();

// Faz o INSERT na tabela propriedade
$sql = "INSERT INTO caderno_propriedade (
    propriedade_cod_usuario,
    propriedade_cnpj,
    propriedade_email,
    propriedade_endereco,
    cidade_cid_cod,
    uf_codigo,
    propriedade_telefone01,
    propriedade_telefone02,
    propriedade_nome
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Erro ao preparar statement INSERT: " . $mysqli->error . " | SQL: $sql");
}
$stmt->bind_param(
    'isssiisss',
    $cod_usuario,
    $cnpj,
    $email,
    $endereco,
    $cidade_cid_cod,
    $uf_codigo,
    $tel1,
    $tel2,
    $nome
);

if ($stmt->execute()) {
    header("Location: https://caderno.frutag.app.br/home/propriedade.php");
    exit();
} else {
    http_response_code(500);
    echo "Erro ao cadastrar: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
