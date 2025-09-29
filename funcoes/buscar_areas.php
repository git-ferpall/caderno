<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Força resposta em JSON
header('Content-Type: application/json; charset=utf-8');

// Pega user_id via sessão ou JWT
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$areas = [];
if ($user_id) {
    // Descobrir a propriedade ativa do usuário
    $stmt = $mysqli->prepare("
        SELECT id 
        FROM propriedades 
        WHERE user_id = ? AND ativo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prop = $res->fetch_assoc();
    $stmt->close();

    if ($prop) {
        $propriedade_id = $prop['id'];

        // Buscar áreas vinculadas a essa propriedade ativa
        $stmt = $mysqli->prepare("
            SELECT id, nome, tipo 
            FROM areas 
            WHERE user_id = ? AND propriedade_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("ii", $user_id, $propriedade_id);
        $stmt->execute();
        $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Retorna sempre JSON (mesmo vazio [])
echo json_encode($areas, JSON_UNESCAPED_UNICODE);
