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
    if (!$user_id) throw new Exception("Usuário não autenticado.");

    // === Recebe propriedades selecionadas (pode ser várias) ===
    $propriedades_ids = $_POST['propriedades'] ?? [];

    // === Lista todas as propriedades ativas do usuário ===
    $stmt = $mysqli->prepare("
        SELECT id, nome_razao 
        FROM propriedades 
        WHERE user_id = ? AND ativo = 1
        ORDER BY nome_razao
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propriedades = [];
    while ($r = $res->fetch_assoc()) $propriedades[] = $r;
    $stmt->close();

    $areas = [];
    $cultivos = [];
    $manejos = [];

    if (!empty($propriedades_ids)) {
        // === Áreas ===
        $ids_str = implode(',', array_map('intval', $propriedades_ids));
        $sqlAreas = "
            SELECT DISTINCT nome 
            FROM areas 
            WHERE propriedade_id IN ($ids_str)
            ORDER BY nome
        ";
        $areas = $mysqli->query($sqlAreas)->fetch_all(MYSQLI_ASSOC);

        // === Cultivos (produtos vinculados às bancadas dessas áreas) ===
        $sqlCultivos = "
            SELECT DISTINCT p.nome 
            FROM produtos p
            JOIN bancadas b ON b.produto_id = p.id
            JOIN areas a ON a.id = b.area_id
            WHERE a.propriedade_id IN ($ids_str)
            ORDER BY p.nome
        ";
        $cultivos = $mysqli->query($sqlCultivos)->fetch_all(MYSQLI_ASSOC);

        // === Tipos de manejo ===
        $sqlManejos = "
            SELECT DISTINCT tipo 
            FROM apontamentos 
            WHERE propriedade_id IN ($ids_str)
            ORDER BY tipo
        ";
        $manejos = $mysqli->query($sqlManejos)->fetch_all(MYSQLI_ASSOC);
    }

    echo json_encode([
        'ok' => true,
        'propriedades' => $propriedades,
        'areas' => array_column($areas, 'nome'),
        'cultivos' => array_column($cultivos, 'nome'),
        'manejos' => array_column($manejos, 'tipo')
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
