<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    session_start();

    // 🔐 Autenticação via JWT ou sessão
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    // 📂 Pasta atual (null = raiz)
    $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? intval($_GET['parent_id']) : null;

    // ===============================
    // 📁 LISTAR PASTAS PRIMEIRO
    // ===============================
    if ($parent_id) {
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem
            FROM silo_arquivos
            WHERE user_id = ? AND tipo = 'pasta' AND parent_id = ?
            ORDER BY nome_arquivo ASC
        ");
        $stmt->bind_param('ii', $user_id, $parent_id);
    } else {
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem
            FROM silo_arquivos
            WHERE user_id = ? AND tipo = 'pasta' AND (parent_id IS NULL OR parent_id = 0)
            ORDER BY nome_arquivo ASC
        ");
        $stmt->bind_param('i', $user_id);
    }

    $stmt->execute();
    $pastas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ===============================
    // 📄 LISTAR ARQUIVOS DA PASTA
    // ===============================
    if ($parent_id) {
        $stmt2 = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem
            FROM silo_arquivos
            WHERE user_id = ? AND tipo = 'arquivo' AND parent_id = ?
            ORDER BY nome_arquivo ASC
        ");
        $stmt2->bind_param('ii', $user_id, $parent_id);
    } else {
        $stmt2 = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem
            FROM silo_arquivos
            WHERE user_id = ? AND tipo = 'arquivo' AND (parent_id IS NULL OR parent_id = 0)
            ORDER BY nome_arquivo ASC
        ");
        $stmt2->bind_param('i', $user_id);
    }

    $stmt2->execute();
    $arquivos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    // ===============================
    // 🔗 COMBINA (pastas primeiro)
    // ===============================
    $itens = array_merge($pastas, $arquivos);

    echo json_encode(['ok' => true, 'arquivos' => $itens], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
    exit;
}
