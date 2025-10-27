<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $stmt = $mysqli->prepare("SELECT id, nome_arquivo, caminho_arquivo FROM silo_arquivos WHERE user_id = ? AND tipo = 'pasta' ORDER BY nome_arquivo");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $pastas = [];
    while ($r = $res->fetch_assoc()) {
        $pastas[] = [
            'id' => $r['id'],
            'nome' => $r['nome_arquivo'],
            'caminho' => $r['caminho_arquivo'],
        ];
    }

    echo json_encode(['ok' => true, 'pastas' => $pastas], JSON_UNESCAPED_UNICODE);
}
catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
