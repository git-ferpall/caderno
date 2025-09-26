<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../configuracao/protect.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

$maquinas = [];

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if ($user_id) {
        // Busca propriedade ativa
        $prop = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
        $prop->bind_param("i", $user_id);
        $prop->execute();
        $res = $prop->get_result()->fetch_assoc();
        $prop_id = $res['id'] ?? null;
        $prop->close();

        if ($prop_id) {
            $stmt = $mysqli->prepare("
                SELECT * 
                FROM maquinas 
                WHERE user_id = ? AND propriedade_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("ii", $user_id, $prop_id);
            $stmt->execute();
            $maquinas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    error_log("Erro ao listar máquinas: " . $e->getMessage());
}
