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
    $parent_id = trim($parent_id);

    // Consulta principal
    if ($parent_id === '' || $parent_id === '0') {
        // Raiz do usuário
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, criado_em, atualizado_em
            FROM silo_arquivos
            WHERE user_id = ? AND (parent_id IS NULL OR parent_id = 0)
            ORDER BY tipo_arquivo DESC, nome_arquivo ASC
        ");
        $stmt->bind_param('i', $user_id);
    } else {
        // Subpasta
        $stmt = $mysqli->prepare("
            SELECT id, nome_arquivo, tipo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, criado_em, atualizado_em
            FROM silo_arquivos
            WHERE user_id = ? AND parent_id = ?
            ORDER BY tipo_arquivo DESC, nome_arquivo ASC
        ");
        $stmt->bind_param('ii', $user_id, $parent_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $arquivos = [];
    while ($row = $res->fetch_assoc()) {
        // 🔧 Normaliza o caminho removendo prefixos redundantes
        $row['caminho_arquivo'] = preg_replace('#^/?(uploads/)?silo/' . $user_id . '/?#', '', $row['caminho_arquivo']);

        // 🧮 Formata tamanho legível
        $row['tamanho_legivel'] = formatarTamanho($row['tamanho_bytes']);

        // 🗂️ Define flag de pasta
        $row['is_folder'] = (
            strtolower($row['tipo']) === 'pasta' ||
            strtolower($row['tipo_arquivo']) === 'folder'
        );

        // 🔖 Normaliza nomes
        $row['nome_exibicao'] = htmlspecialchars($row['nome_arquivo']);

        // ⏱️ Formata datas
        $row['criado_em'] = date('d/m/Y H:i', strtotime($row['criado_em']));
        $row['atualizado_em'] = date('d/m/Y H:i', strtotime($row['atualizado_em']));

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
    if (!is_numeric($bytes) || $bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
