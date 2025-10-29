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
    error_log("📥 POST recebido: " . json_encode($_POST));

    // === Lista todas as propriedades do usuário ===
    $stmt = $mysqli->prepare("
        SELECT id, nome_razao, ativo 
        FROM propriedades 
        WHERE user_id = ?
        ORDER BY ativo DESC, nome_razao ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $propriedades = [];
    while ($r = $res->fetch_assoc()) $propriedades[] = $r;
    $stmt->close();

    // === Inicializa arrays ===
    $areas = [];
    $cultivos = [];
    $manejos = [];

    if (!empty($propriedades_ids)) {
        $ids_str = implode(',', array_map('intval', $propriedades_ids));
        error_log("🔎 Buscando filtros para propriedades: " . $ids_str);

        // === Áreas ===
        $sqlAreas = "
            SELECT DISTINCT a.nome 
            FROM areas a
            WHERE a.propriedade_id IN ($ids_str)
            ORDER BY a.nome
        ";
        $res = $mysqli->query($sqlAreas);
        if ($res) $areas = $res->fetch_all(MYSQLI_ASSOC);

        // === Cultivos (produtos vinculados às bancadas das áreas) ===
        $sqlCultivos = "
            SELECT DISTINCT p.nome 
            FROM produtos p
            JOIN bancadas b ON b.produto_id = p.id
            JOIN areas a ON a.id = b.area_id
            WHERE a.propriedade_id IN ($ids_str)
            ORDER BY p.nome
        ";
        $res = $mysqli->query($sqlCultivos);
        if ($res) $cultivos = $res->fetch_all(MYSQLI_ASSOC);

        // === Tipos de manejo ===
        $sqlManejos = "
            SELECT DISTINCT a.tipo 
            FROM apontamentos a
            WHERE a.propriedade_id IN ($ids_str)
            ORDER BY a.tipo
        ";
        $res = $mysqli->query($sqlManejos);
        if ($res) $manejos = $res->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("⚠️ Nenhuma propriedade selecionada recebida.");
    }

    echo json_encode([
        'ok' => true,
        'propriedades' => $propriedades,
        'areas' => array_column($areas, 'nome'),
        'cultivos' => array_column($cultivos, 'nome'),
        'manejos' => array_column($manejos, 'tipo')
    ]);

} catch (Exception $e) {
    error_log("❌ Erro buscar_filtros_relatorio: " . $e->getMessage());
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
