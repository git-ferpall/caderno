<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

/**
 * 🧹 Remove diretório e todo o conteúdo dentro (recursivamente)
 */
function removerDiretorio($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = "$dir/$item";
        if (is_dir($path)) {
            removerDiretorio($path);
        } else {
            @unlink($path);
        }
    }

    return @rmdir($dir);
}

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('Usuário não autenticado');

    // 📦 ID recebido
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) throw new Exception('ID inválido');

    // 🔍 Busca arquivo/pasta
    $stmt = $mysqli->prepare("SELECT * FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('Arquivo ou pasta não encontrada');

    $tipo = $res['tipo'];
    $caminho_rel = $res['caminho_arquivo'];
    $base = '/var/www/html/uploads';
    $caminho_abs = "$base/$caminho_rel";

    // 🧹 Exclusão física
    if ($tipo === 'pasta') {
        if (!removerDiretorio($caminho_abs)) {
            throw new Exception('Falha ao remover pasta física');
        }
    } else {
        if (file_exists($caminho_abs) && !@unlink($caminho_abs)) {
            throw new Exception('Falha ao excluir arquivo');
        }
    }

    // 🗄️ Remove do banco (arquivos e subpastas)
    $stmtDel = $mysqli->prepare("
        DELETE FROM silo_arquivos 
        WHERE id = ? OR parent_id = ?
    ");
    $stmtDel->bind_param("ii", $id, $id);
    $stmtDel->execute();
    $stmtDel->close();

    echo json_encode([
        'ok' => true,
        'msg' => '🗑️ Item removido com sucesso!'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
