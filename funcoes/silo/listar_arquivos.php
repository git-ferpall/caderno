<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 📁 listar_arquivos.php
 * Lista arquivos e pastas do silo de dados do usuário
 */

try {
    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // Pasta atual (por ID ou caminho)
    $parent_id = $_GET['parent_id'] ?? '';

    // Consulta principal
    if ($parent_id === '' || $parent_id === '0') {
        // Raiz do usuário
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, criado_em, atualizado_em
            FROM silo_arquivos
            WHERE user_id = ? AND (parent_id IS NULL OR parent_id = 0)
            ORDER BY tipo ASC, nome_arquivo ASC
        ");
        $stmt->bind_param('i', $user_id);
    } else {
        // Subpasta
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, criado_em, atualizado_em
            FROM silo_arquivos
            WHERE user_id = ? AND parent_id = ?
            ORDER BY tipo ASC, nome_arquivo ASC
        ");
        $stmt->bind_param('ii', $user_id, $parent_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $arquivos = [];
    while ($row = $res->fetch_assoc()) {
        // 🔧 Normaliza o caminho removendo 'silo/{user_id}/'
        $row['caminho_arquivo'] = preg_replace('#^silo/' . $user_id . '/?#', '', $row['caminho_arquivo']);

        // Formata tamanho legível
        $row['tamanho_legivel'] = formatarTamanho($row['tamanho_bytes']);

        // Adiciona tipo genérico
        $row['is_folder'] = ($row['tipo'] === 'pasta' || $row['tipo_arquivo'] === 'folder');

        $arquivos[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'arquivos' => $arquivos,
        'msg' => 'Listagem concluída com sucesso',
        'path' => ($parent_id === '' || $parent_id === '0') ? 'raiz' : $parent_id
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}

/**
 * 🧮 Formata tamanho em bytes para KB/MB/GB
 */
function formatarTamanho($bytes)
{
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i - 1];
}
