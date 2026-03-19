<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {

    /* ===============================
    🔐 AUTENTICAÇÃO
    =============================== */

    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        throw new Exception("Usuário não autenticado");
    }

    /* ===============================
    🏡 PROPRIEDADES
    =============================== */

    $stmt = $mysqli->prepare("
        SELECT id, nome_razao
        FROM propriedades
        WHERE user_id = ?
        ORDER BY nome_razao
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $propriedades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();


    /* ===============================
    🌱 ÁREAS (opcional por propriedade)
    =============================== */

    $areas = [];

    if (!empty($_GET['propriedade_id'])) {

        $prop_id = intval($_GET['propriedade_id']);

        $stmt = $mysqli->prepare("
            SELECT id, nome
            FROM areas
            WHERE propriedade_id = ?
            ORDER BY nome
        ");
        $stmt->bind_param("i", $prop_id);
        $stmt->execute();

        $areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    /* ===============================
    🚀 RESPOSTA
    =============================== */

    echo json_encode([
        "ok" => true,
        "propriedades" => $propriedades,
        "areas" => $areas
    ]);

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "err" => $e->getMessage()
    ]);
}