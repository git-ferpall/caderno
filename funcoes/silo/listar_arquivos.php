<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $res = $mysqli->query("SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, criado_em FROM silo_arquivos WHERE user_id = $user_id ORDER BY criado_em DESC");
    $dados = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['ok' => true, 'arquivos' => $dados]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
