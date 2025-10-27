<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔒 Autenticação via JWT ou sessão
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    // 📁 Pasta atual (raiz se vazio)
    $pasta_id = isset($_GET['pasta']) && $_GET['pasta'] !== '' ? intval($_GET['pasta']) : null;

    // 🗂️ Busca arquivos e pastas da pasta atual
    if ($pasta_id) {
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, pasta, origem 
            FROM silo_arquivos 
            WHERE user_id = ? AND (pasta = ?)
            ORDER BY tipo_arquivo = 'folder' DESC, nome_arquivo ASC
        ");
        $stmt->bind_param('ii', $user_id, $pasta_id);
    } else {
        // raiz = arquivos sem pasta
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, pasta, origem 
            FROM silo_arquivos 
            WHERE user_id = ? AND (pasta IS NULL OR pasta = '')
            ORDER BY tipo_arquivo = 'folder' DESC, nome_arquivo ASC
        ");
        $stmt->bind_param('i', $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        // Garante consistência
        $arquivos[] = [
            'id'            => (int)$row['id'],
            'nome_arquivo'  => $row['nome_arquivo'],
            'tipo_arquivo'  => $row['tipo_arquivo'] ?: 'file',
            'tamanho_bytes' => (int)$row['tamanho_bytes'],
            'pasta'         => $row['pasta'],
            'origem'        => $row['origem'],
        ];
    }

    echo json_encode(['ok' => true, 'arquivos' => $arquivos], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
    exit;
}