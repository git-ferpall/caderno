<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');

    if ($id <= 0 || $novo_nome === '') throw new Exception('invalid_params');

    // Sanitiza o nome: remove caracteres perigosos
    $novo_nome = preg_replace('/[^A-Za-z0-9_\-\. ]/', '', $novo_nome);

    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('sii', $novo_nome, $id, $user_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) throw new Exception('db_update_failed');

    echo json_encode(['ok' => true, 'msg' => 'Arquivo renomeado com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
