<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

// Força resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Inicia a sessão e tenta pegar user_id
session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Se não tiver sessão, tenta via JWT
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

$produtos = [];

if ($user_id) {
    try {
        $stmt = $mysqli->prepare("
            SELECT id, nome
            FROM produtos
            WHERE user_id = ?
            ORDER BY nome ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Throwable $e) {
        error_log("Erro buscar_produtos: " . $e->getMessage());
    }
}

// Retorna JSON sempre (mesmo vazio)
echo json_encode($produtos, JSON_UNESCAPED_UNICODE);
