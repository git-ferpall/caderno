<?php
require_once __DIR__ . '/funcoes_silo.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {

    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    $id = intval($_POST['id'] ?? 0);
    $destino_id = intval($_POST['destino_id'] ?? 0);
    var_dump($_POST);
    exit;
    if ($id <= 0) {
        throw new Exception('ID inválido.');
    }

    // 📁 Caminho base físico real
    $base = "/var/www/html/uploads/silo/$user_id";

    if (!is_dir($base)) {
        throw new Exception('Diretório base não encontrado.');
    }

    // ===============================
    // 🔎 BUSCA ITEM NO BANCO
    // ===============================

    $stmt = $mysqli->prepare("
        SELECT id, nome_arquivo, caminho_arquivo, tipo
        FROM silo_arquivos
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        throw new Exception('Item não encontrado.');
    }

    // Remove prefixo silo/USER_ID/
    $caminho_rel = preg_replace('#^silo/' . $user_id . '/#', '', $item['caminho_arquivo']);

    $origem_abs = $base . '/' . $caminho_rel;

    if (!file_exists($origem_abs)) {
        throw new Exception('Arquivo físico não encontrado: ' . $origem_abs);
    }

    // ===============================
    // 📂 DESTINO
    // ===============================

    if ($destino_id > 0) {

        $stmt = $mysqli->prepare("
            SELECT nome_arquivo
            FROM silo_arquivos
            WHERE id = ? AND user_id = ? AND tipo = 'pasta'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $destino_id, $user_id);
        $stmt->execute();
        $dest = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$dest) {
            throw new Exception('Pasta destino não encontrada.');
        }

        $destino_abs = $base . '/' . $dest['nome_arquivo'];

        if (!is_dir($destino_abs)) {
            throw new Exception('Diretório físico da pasta destino não existe.');
        }

        $novo_parent_id = $destino_id;

        $novo_caminho_rel = $dest['nome_arquivo'] . '/' . basename($caminho_rel);

    } else {
        // mover para raiz
        $destino_abs = $base;
        $novo_parent_id = null;
        $novo_caminho_rel = basename($caminho_rel);
    }

    $novo_abs = $destino_abs . '/' . basename($caminho_rel);

    // 🚫 Mesmo local
    if (realpath($origem_abs) === realpath($novo_abs)) {
        throw new Exception('O item já está nesse local.');
    }

    // 🚫 Evita sobrescrever
    if (file_exists($novo_abs)) {
        throw new Exception('Já existe um arquivo com esse nome no destino.');
    }

    // ===============================
    // 🚚 MOVE FÍSICO
    // ===============================

    if (!rename($origem_abs, $novo_abs)) {
        throw new Exception('Erro ao mover o arquivo.');
    }

    // ===============================
    // 💾 ATUALIZA BANCO
    // ===============================

    $novo_caminho_banco = "silo/$user_id/" . $novo_caminho_rel;

    $stmt = $mysqli->prepare("
        UPDATE silo_arquivos
        SET caminho_arquivo = ?, parent_id = ?, atualizado_em = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("siii", $novo_caminho_banco, $novo_parent_id, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => '📦 Item movido com sucesso!'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}