<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => true, 'caminho' => []]); // raiz
        exit;
    }

    // Busca hierarquia recursiva até a raiz
    $caminho = [];
    $atual = $id;

    while ($atual) {
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, parent_id 
            FROM silo_arquivos 
            WHERE id = ? AND user_id = ? AND tipo = 'pasta'
        ");
        $stmt->bind_param('ii', $atual, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) break;

        array_unshift($caminho, [
            'id' => (int)$res['id'],
            'nome' => $res['nome_arquivo']
        ]);

        $atual = $res['parent_id'];
    }

    echo json_encode(['ok' => true, 'caminho' => $caminho], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
