<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
header('Content-Type: application/json');

$payload = verify_jwt();
$user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

if (!$user_id) { echo json_encode(['ok' => false]); exit; }

$stmt = $mysqli->prepare("SELECT armazenamento FROM cliente WHERE cli_cod = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$limite_gb = (float)($res['armazenamento'] ?? 1.00);

$user_dir = __DIR__ . "/../../uploads/$user_id";
$total_usado = 0;
if (is_dir($user_dir)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($user_dir));
    foreach ($files as $f) if ($f->isFile()) $total_usado += $f->getSize();
}

$usado_gb = round($total_usado / (1024 * 1024 * 1024), 2);
$percent = ($usado_gb / $limite_gb) * 100;

echo json_encode(['ok'=>true, 'usado'=>$usado_gb, 'limite'=>$limite_gb, 'percent'=>round($percent,1)]);
