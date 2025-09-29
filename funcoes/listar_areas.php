<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Pega user_id via sessão ou JWT
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$areas = [];
if ($user_id) {
    // Descobrir a propriedade ativa
    $stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res  = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if ($prop) {
        $propriedade_id = $prop['id'];

        // Pegar somente áreas dessa propriedade
        $stmt = $mysqli->prepare("SELECT * FROM areas WHERE user_id = ? AND propriedade_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("ii", $user_id, $propriedade_id);
        $stmt->execute();
        $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
