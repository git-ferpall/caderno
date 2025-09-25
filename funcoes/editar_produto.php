<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
header('Content-Type: application/json; charset=utf-8');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

$id   = intval($_POST['id'] ?? 0);
$nome = trim($_POST['pnome'] ?? '');
$tipo = $_POST['ptipo'] ?? '';
$atr  = $_POST['patr'] ?? '';

if (!$user_id || $id <= 0 || $nome === '') {
    echo json_encode(["ok" => false, "error" => "Dados inválidos"]);
    exit;
}

$mapTipo = ['1'=>'convencional','2'=>'organico','3'=>'integrado'];
$mapAtr  = ['hidro'=>'hidro','semi-hidro'=>'semi-hidro','solo'=>'solo'];

$tipoVal = $mapTipo[$tipo] ?? null;
$atrVal  = $mapAtr[$atr] ?? null;

$stmt = $mysqli->prepare("UPDATE produtos SET nome = ?, tipo = ?, atributo = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sssii", $nome, $tipoVal, $atrVal, $id, $user_id);

echo $stmt->execute()
    ? json_encode(["ok" => true])
    : json_encode(["ok" => false, "error" => $stmt->error]);
