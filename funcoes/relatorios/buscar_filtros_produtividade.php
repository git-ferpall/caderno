<?php

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json');

try {

    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;

    if (!$user_id) {
        throw new Exception("Usuário não autenticado");
    }

    $propriedade = $_POST['propriedade'] ?? null;

    /* ===============================
       PROPRIEDADES
    =============================== */

    $propriedades = [];

    $stmt = $mysqli->prepare("
        SELECT id, nome_razao
        FROM propriedades
        WHERE user_id = ?
        ORDER BY nome_razao
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $propriedades[] = $row;
    }

    $stmt->close();

    /* ===============================
       ÁREAS
    =============================== */

    $areas = [];

    if ($propriedade) {

        // áreas filtradas pela propriedade

        $stmt = $mysqli->prepare("
            SELECT id, nome
            FROM areas
            WHERE propriedade_id = ?
            ORDER BY nome
        ");

        $stmt->bind_param("i", $propriedade);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $areas[] = $row;
        }

        $stmt->close();

    } else {

        // todas as áreas do usuário

        $stmt = $mysqli->prepare("
            SELECT a.id, a.nome
            FROM areas a
            JOIN propriedades p ON p.id = a.propriedade_id
            WHERE p.user_id = ?
            ORDER BY a.nome
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $areas[] = $row;
        }

        $stmt->close();

    }

    /* ===============================
       PRODUTOS
    =============================== */

    $produtos = [];

    $res = $mysqli->query("
        SELECT id, nome
        FROM produtos
        ORDER BY nome
    ");

    while ($row = $res->fetch_assoc()) {
        $produtos[] = $row;
    }

    /* ===============================
       RETORNO
    =============================== */

    echo json_encode([
        "ok" => true,
        "propriedades" => $propriedades,
        "areas" => $areas,
        "produtos" => $produtos
    ]);

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "err" => $e->getMessage()
    ]);

}