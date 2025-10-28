<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $q = trim($_GET['q'] ?? '');
    if ($q === '') throw new Exception('vazio');

    // Pesquisa por nome (parcial)
    $stmt = $mysqli->prepare("
        SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id
        FROM silo_arquivos
        WHERE user_id = ? AND nome_arquivo LIKE CONCAT('%', ?, '%')
        ORDER BY tipo_arquivo DESC, nome_arquivo ASC
        LIMIT 100
    ");
    $stmt->bind_param("is", $user_id, $q);
    $stmt->execute();
    $res = $stmt->get_result();
    $arquivos = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['ok' => true, 'arquivos' => $arquivos]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
