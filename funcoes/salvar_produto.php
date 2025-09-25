<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// garante que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1️⃣ Tenta pegar user_id da sessão
$user_id = $_SESSION['user_id'] ?? null;

// 2️⃣ Se não tiver na sessão, tenta pelo JWT
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

// 3️⃣ Se não tiver nenhum → bloqueia
if (!$user_id) {
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
    exit;
}

// Recupera os dados enviados
$nome = trim($_POST['pnome'] ?? '');
$tipo = $_POST['ptipo'] ?? '';
$atr  = $_POST['patr'] ?? '';

// Validação básica
if ($nome === '' || $tipo === '' || $atr === '') {
    echo json_encode(["ok" => false, "error" => "Dados incompletos"]);
    exit;
}

// Mapeamento para ENUM
$mapTipo = ['1' => 'convencional', '2' => 'organico', '3' => 'integrado'];
$mapAtr  = ['hidro' => 'hidro', 'semi-hidro' => 'semi-hidro', 'solo' => 'solo'];

$tipoVal = $mapTipo[$tipo] ?? null;
$atrVal  = $mapAtr[$atr] ?? null;

if (!$tipoVal || !$atrVal) {
    echo json_encode(["ok" => false, "error" => "Valores inválidos"]);
    exit;
}

// Inserção no banco
$stmt = $mysqli->prepare("INSERT INTO produtos (user_id, nome, tipo, atributo) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["ok" => false, "error" => "Erro prepare: " . $mysqli->error]);
    exit;
}

$stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);

if ($stmt->execute()) {
    echo json_encode(["ok" => true, "id" => $stmt->insert_id]);
} else {
    echo json_encode(["ok" => false, "error" => "Erro execute: " . $stmt->error]);
}
$stmt->close();
