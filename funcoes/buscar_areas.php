<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');

// Descobre user_id da sessão ou do JWT
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$areas = [];

if ($user_id) {
   // Lista todas as áreas vinculadas à propriedade ativa
    $stmt = $mysqli->prepare("SELECT id, nome_razao AS nome FROM areas WHERE propriedade_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $propriedade_id);
    $stmt->execute();
    $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($prop) {
        $propriedade_id = $prop['id'];

        // Buscar áreas SOMENTE da propriedade ativa
        $stmt = $mysqli->prepare("SELECT id, nome FROM areas WHERE propriedade_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $propriedade_id);
        $stmt->execute();
        $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

echo json_encode($areas);
