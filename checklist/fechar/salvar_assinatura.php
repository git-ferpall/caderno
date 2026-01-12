<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

$user = require_login();
$user_id = (int)$user->sub;

$data = json_decode(file_get_contents('php://input'), true);

$checklist_id = (int)($data['checklist_id'] ?? 0);
$img = $data['imagem'] ?? '';

if (!$checklist_id || !$img) {
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}

$img = str_replace('data:image/png;base64,', '', $img);
$img = base64_decode($img);

$dir = __DIR__ . "/../../uploads/checklists/$checklist_id";
if (!is_dir($dir)) mkdir($dir, 0775, true);

$arquivo = "assinatura.png";
file_put_contents("$dir/$arquivo", $img);

$stmt = $mysqli->prepare("
    INSERT INTO checklist_assinaturas
        (checklist_id, usuario_id, arquivo, ip, user_agent)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iisss",
    $checklist_id,
    $user_id,
    $arquivo,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true]);
