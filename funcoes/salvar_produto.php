<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

// 🔎 DEBUG inicial
error_log("DEBUG salvar_produto.php INICIO");

// garante que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// mostra sessão
error_log("DEBUG SESSION: " . print_r($_SESSION, true));

// tenta pegar user_id da sessão
$user_id = $_SESSION['user_id'] ?? null;

// se não tiver, tenta JWT
if (!$user_id) {
    $payload = verify_jwt();
    error_log("DEBUG JWT: " . print_r($payload, true));
    $user_id = $payload['sub'] ?? null;
}

error_log("DEBUG USER_ID = " . var_export($user_id, true));

if (!$user_id) {
    error_log("DEBUG: user_id vazio → bloqueando");
    echo json_encode(["ok" => false, "error" => "Usuário não autenticado"]);
    exit;
}

// Recupera os dados do POST
$nome = trim($_POST['pnome'] ?? '');
$tipo = $_POST['ptipo'] ?? '';
$atr  = $_POST['patr'] ?? '';

// loga dados recebidos
error_log("DEBUG POST: pnome=$nome | ptipo=$tipo | patr=$atr");

// validação básica
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

// prepara SQL
$stmt = $mysqli->prepare("INSERT INTO produtos (user_id, nome, tipo, atributo) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["ok" => false, "error" => "Erro prepare: " . $mysqli->error]);
    exit;
}

$stmt->bind_param("isss", $user_id, $nome, $tipoVal, $atrVal);

// executa
if ($stmt->execute()) {
    error_log("DEBUG: Produto inserido com sucesso! ID=" . $stmt->insert_id);
    echo json_encode(["ok" => true, "id" => $stmt->insert_id]);
} else {
    error_log("DEBUG: Erro execute: " . $stmt->error);
    echo json_encode(["ok" => false, "error" => "Erro execute: " . $stmt->error]);
}

$stmt->close();
