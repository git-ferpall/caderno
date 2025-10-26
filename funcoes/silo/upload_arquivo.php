<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'unauthorized']);
    exit;
}

if (empty($_FILES['arquivo']['name'])) {
    echo json_encode(['ok' => false, 'err' => 'no_file']);
    exit;
}

$permitidos = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
$tipo = mime_content_type($_FILES['arquivo']['tmp_name']);

if (!in_array($tipo, $permitidos)) {
    echo json_encode(['ok' => false, 'err' => 'invalid_type']);
    exit;
}

$tamanho = $_FILES['arquivo']['size'];
$nome = basename($_FILES['arquivo']['name']);
$origem = $_POST['origem'] ?? 'upload';

// Pasta de upload por usuário
$user_dir = __DIR__ . "/../../uploads/$user_id";
if (!is_dir($user_dir)) mkdir($user_dir, 0775, true);

// Calcula limite
$stmt = $mysqli->prepare("SELECT armazenamento FROM cliente WHERE cli_cod = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$limite_gb = (float)($res['armazenamento'] ?? 1.00);
$limite_bytes = $limite_gb * 1024 * 1024 * 1024;

// Calcula espaço usado
$total_usado = 0;
if (is_dir($user_dir)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($user_dir));
    foreach ($files as $f) if ($f->isFile()) $total_usado += $f->getSize();
}

if ($total_usado + $tamanho > $limite_bytes) {
    echo json_encode(['ok' => false, 'err' => 'limite_excedido']);
    exit;
}

$caminho_final = "$user_dir/$nome";

if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_final)) {
    echo json_encode(['ok' => false, 'err' => 'upload_fail']);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, origem)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ississ", $user_id, $nome, $tipo, $tamanho, $caminho_final, $origem);
$stmt->execute();

echo json_encode(['ok' => true, 'arquivo' => $nome]);
