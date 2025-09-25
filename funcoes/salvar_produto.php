<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// pega user_id do JWT ou sessão
$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

if (!$user_id) {
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
    exit;
}

$nome = trim($_POST['pnome'] ?? '');
$tipo = $_POST['ptipo'] ?? '';
$atr  = $_POST['patr'] ?? '';

if ($nome === '' || $tipo === '' || $atr === '') {
    echo json_encode(["ok" => false, "error" => "Dados incompletos"]);
    exit;
}

$mapTipo = ['1'=>'convencional','2'=>'organico','3'=>'integrado'];
$mapAtr  = ['hidro'=>'hidro','semi-hidro'=>'semi-hidro','solo'=>'solo'];

$tipoVal = $mapTipo[$tipo] ?? null;
$atrVal  = $mapAtr[$atr] ?? null;

if (!$tipoVal || !$atrVal) {
    echo json_encode(["ok" => false, "error" => "Valores inválidos"]);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO produtos (user_id, nome, tipo, atributo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);

if ($stmt->execute()) {
    echo json_encode(["ok" => true, "id" => $stmt->insert_id]);
} else {
    echo json_encode(["ok" => false, "error" => $stmt->error]);
}
$stmt->close();
