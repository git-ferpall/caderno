<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) throw new Exception('unauthorized');

    if (!isset($_FILES['arquivo'])) {
        echo json_encode(['ok' => false, 'err' => 'no_file']);
        exit;
    }

    $file = $_FILES['arquivo'];
    $origem = $_POST['origem'] ?? 'upload';

    // Diretório de upload
    $uploadDir = __DIR__ . '/../../../uploads/silo/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    // Gera nome único
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nome_final = uniqid('', true) . '.' . strtolower($ext);
    $destino = $uploadDir . $nome_final;

    // Verifica tipos permitidos
    $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    if (!in_array($file['type'], $permitidos)) {
        echo json_encode(['ok' => false, 'err' => 'tipo_não_permitido']);
        exit;
    }

    // Move arquivo
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        echo json_encode(['ok' => false, 'err' => 'erro_upload']);
        exit;
    }

    // Grava no banco
    $ok = salvarArquivo($mysqli, $user_id, $nome_final, $file['type'], $file['size'], $origem);

    echo json_encode(['ok' => $ok]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
