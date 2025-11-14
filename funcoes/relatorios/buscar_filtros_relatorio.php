<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    // === Autenticação ===
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $payload = verify_jwt();
        $user_id = $payload['sub'] ?? null;
    }

    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // === Busca todas as propriedades do usuário ===
    $stmt = $mysqli->prepare("
        SELECT id, nome_razao, ativo
        FROM propriedades
        WHERE user_id = ?
        ORDER BY nome_razao
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propriedades = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // === Caso nenhuma propriedade esteja selecionada ===
    if (empty($_POST['propriedades'])) {
        echo json_encode([
            'ok' => true,
            'propriedades' => $propriedades,
            'areas' => [],
            'cultivos' => [],
            'manejos' => []
        ]);
        exit;
    }

    // === Filtra pelas propriedades selecionadas ===
    $propriedades_sel = array_map('intval', $_POST['propriedades']);
    $placeholders = implode(',', array_fill(0, count($propriedades_sel), '?'));

    // === Monta tipos dinamicamente para bind_param ===
    $types = str_repeat('i', count($propriedades_sel));

    // --- Áreas ---
    $sql_areas = "
        SELECT DISTINCT a.nome 
        FROM areas a
        WHERE a.propriedade_id IN ($placeholders)
        ORDER BY a.nome
    ";
    $stmt = $mysqli->prepare($sql_areas);
    $stmt->bind_param($types, ...$propriedades_sel);
    $stmt->execute();
    $areas = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nome');
    $stmt->close();

    // --- Cultivos (produtos cadastrados pelo usuário) ---
    $stmt = $mysqli->prepare("
        SELECT DISTINCT nome 
        FROM produtos 
        WHERE user_id = ? 
        ORDER BY nome
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cultivos = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nome');
    $stmt->close();


    // --- Tipos de manejo (apontamentos registrados) ---
    $sql_manejos = "
        SELECT DISTINCT a.tipo
        FROM apontamentos a
        WHERE a.propriedade_id IN ($placeholders)
        ORDER BY a.tipo
    ";
    $stmt = $mysqli->prepare($sql_manejos);
    $stmt->bind_param($types, ...$propriedades_sel);
    $stmt->execute();
    $manejos = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'tipo');
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'propriedades' => $propriedades,
        'areas' => $areas,
        'cultivos' => $cultivos,
        'manejos' => $manejos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ]);
}
