<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

// --- 1. Identifica usuário autenticado ---
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}
if (!$user_id) {
    echo json_encode(['ok' => false, 'err' => 'Usuário não autenticado']);
    exit;
}

// --- 2. Descobre a propriedade ativa ---
$stmt = $mysqli->prepare("SELECT id FROM propriedades WHERE user_id = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$prop = $res->fetch_assoc();
$stmt->close();

if (!$prop) {
    echo json_encode(['ok' => false, 'err' => 'Nenhuma propriedade ativa encontrada']);
    exit;
}
$propriedade_id = $prop['id'];

// --- 3. Busca as áreas do tipo 'estufa' ---
$sqlAreas = "SELECT id, nome, tipo, created_at FROM areas 
             WHERE propriedade_id = ? AND tipo = 'estufa'
             ORDER BY nome ASC";
$stmt = $mysqli->prepare($sqlAreas);
$stmt->bind_param("i", $propriedade_id);
$stmt->execute();
$resAreas = $stmt->get_result();

$areas = [];

while ($area = $resAreas->fetch_assoc()) {
    $area_id = $area['id'];

    // --- 4. Busca estufas ligadas à área ---
    $stmt2 = $mysqli->prepare("SELECT id, nome, area_m2, obs, created_at 
                               FROM estufas 
                               WHERE area_id = ?
                               ORDER BY nome ASC");
    $stmt2->bind_param("i", $area_id);
    $stmt2->execute();
    $resEstufas = $stmt2->get_result();

    $estufas = [];
    while ($estufa = $resEstufas->fetch_assoc()) {
        $estufa_id = $estufa['id'];

        // --- 5. Busca bancadas da estufa ---
        $stmt3 = $mysqli->prepare("SELECT id, nome, cultura, obs, created_at 
                                   FROM bancadas 
                                   WHERE estufa_id = ?
                                   ORDER BY nome ASC");
        $stmt3->bind_param("i", $estufa_id);
        $stmt3->execute();
        $resBancadas = $stmt3->get_result();
        $bancadas = $resBancadas->fetch_all(MYSQLI_ASSOC);
        $stmt3->close();

        $estufa['bancadas'] = $bancadas;
        $estufas[] = $estufa;
    }
    $stmt2->close();

    $area['estufas'] = $estufas;
    $areas[] = $area;
}
$stmt->close();

// --- 6. Retorna tudo em JSON ---
echo json_encode([
    'ok' => true,
    'areas' => $areas
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
