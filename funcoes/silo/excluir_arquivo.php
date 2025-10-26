<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('invalid_id');

    $stmt = $mysqli->prepare("SELECT caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('arquivo_nao_encontrado');

    $path = "/var/www/html/" . ltrim($res['caminho_arquivo'], '/');
    if (file_exists($path)) unlink($path);

    $mysqli->query("DELETE FROM silo_arquivos WHERE id = $id AND user_id = $user_id");

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
