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

    if ($id <= 0) {
        throw new Exception('ID inválido.');
    }

    // 📁 Caminho base real dentro do container
    $base = "/var/www/html/uploads/silo/$user_id";

    if (!is_dir($base)) {
        throw new Exception('Diretório base do usuário não encontrado.');
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

    // ===============================
    // 📂 CAMINHO ORIGEM REAL
    // ===============================

    // Sempre usar apenas o nome físico
    $nome_fisico = basename($item['caminho_arquivo']);
    $origem_abs = $base . '/' . $nome_fisico;

    if (!file_exists($origem_abs)) {
        throw new Exception('Arquivo físico não encontrado: ' . $origem_abs);
    }

    // ===============================
    // 📁 DESTINO
    // ===============================

    if ($destino_id > 0) {

        // Busca pasta destino
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

    } else {
        // Raiz
        $destino_abs = $base;
        $novo_parent_id = null;
    }

    $novo_abs = $destino_abs . '/' . $nome_fisico;

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

    $stmt = $mysqli->prepare("
        UPDATE silo_arquivos
        SET parent_id = ?, atualizado_em = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("iii", $novo_parent_id, $id, $user_id);
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