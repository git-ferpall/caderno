<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    // 📂 Pega o ID da pasta atual (null = raiz)
    $parent_id = isset($_GET['parent_id']) && $_GET['parent_id'] !== ''
        ? intval($_GET['parent_id'])
        : null;

    // 🔎 Busca arquivos e pastas
    $stmt = $mysqli->prepare("
        SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, criado_em, tipo
        FROM silo_arquivos
        WHERE user_id = ? AND " . ($parent_id ? "parent_id = ?" : "parent_id IS NULL") . "
        ORDER BY tipo DESC, nome_arquivo ASC
    ");

    if ($parent_id) {
        $stmt->bind_param("ii", $user_id, $parent_id);
    } else {
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $arquivos = [];
    while ($row = $res->fetch_assoc()) {
        $arquivos[] = [
            'id' => (int) $row['id'],
            'nome_arquivo' => $row['nome_arquivo'],
            'tipo_arquivo' => $row['tipo_arquivo'],
            'tamanho_bytes' => (int) $row['tamanho_bytes'],
            'origem' => $row['origem'],
            'criado_em' => $row['criado_em'],
            'tipo' => $row['tipo'], // 'arquivo' ou 'pasta'
        ];
    }

    echo json_encode(['ok' => true, 'arquivos' => $arquivos], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
