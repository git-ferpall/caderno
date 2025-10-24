<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json');
session_start();

$estufa_id = $_POST['estufa_id'] ?? null;
$area_id = $_POST['area_id'] ?? null;

if (!$estufa_id || !$area_id) {
    echo json_encode(['ok' => false, 'err' => 'Dados incompletos']);
    exit;
}

$stmt = $mysqli->prepare("INSERT IGNORE INTO estufa_areas (estufa_id, area_id) VALUES (?, ?)");
$stmt->bind_param("ii", $estufa_id, $area_id);
$ok = $stmt->execute();

echo json_encode(['ok' => $ok]);
