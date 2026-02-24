<?php
require_once __DIR__ . '/funcoes_silo.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * 📦 mover_arquivo.php
 * Move arquivos ou pastas dentro do silo do usuário
 */

try {

    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);

    if (!$user_id) {
        throw new Exception('unauthorized');
    }

    $id = intval($_POST['id'] ?? 0);
    $destino = trim($_POST['destino'] ?? '');

    if ($id <= 0) {
        throw new Exception('Parâmetro ID inválido.');
    }

    // 📁 Caminho base do usuário
    $base = "/var/www/html/uploads/silo/$user_id";

    if (!is_dir($base)) {
        throw new Exception('Diretório base do usuário não encontrado.');
    }

    // ===============================
    // 🏠 DESTINO
    // ===============================

    if ($destino === '' || $destino === '0' || strtolower($destino) === 'raiz') {

        $destino_abs = $base;
        $destino_rel = "silo/$user_id";
        $novo_parent_id = null;

    } else {

        // Remove prefixos redundantes
        $destino = preg_replace('#^silo/' . $user_id . '/?#', '', $destino);
        $destino = trim($destino, '/');

        $destino_rel = "silo/$user_id/$destino";
        $destino_abs = "/var/www/html/uploads/$destino_rel";

        if (!is_dir($destino_abs)) {
            throw new Exception("Destino inválido ou inexistente.");
        }

        // Descobre parent_id da pasta destino
        $stmt = $mysqli->prepare("
            SELECT id 
            FROM silo_arquivos 
            WHERE caminho_arquivo = ? 
              AND user_id = ? 
              AND tipo = 'pasta' 
            LIMIT 1
        ");
        $stmt->bind_param("si", $destino_rel, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $novo_parent_id = $res['id'] ?? null;
    }

    // ===============================
    // 🔎 BUSCA ITEM
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
    // 📂 CAMINHO ORIGEM (BLINDADO)
    // ===============================

    // Primeiro tenta caminho completo
    $origem_abs = "/var/www/html/uploads/" . ltrim($item['caminho_arquivo'], '/');

    // Se não existir, tenta fallback (caso banco tenha salvo só nome)
    if (!file_exists($origem_abs)) {
        $origem_abs = $base . '/' . basename($item['caminho_arquivo']);
    }

    if (!file_exists($origem_abs)) {
        throw new Exception('Arquivo/pasta física não encontrada.');
    }

    // ===============================
    // 🆕 MONTA NOVO CAMINHO
    // ===============================

    $novo_nome = basename($item['caminho_arquivo']);
    $novo_caminho_rel = rtrim($destino_rel, '/') . '/' . $novo_nome;
    $novo_abs = "/var/www/html/uploads/" . $novo_caminho_rel;

    // 🚫 Evita mover para o mesmo local
    if (realpath($origem_abs) === realpath($novo_abs)) {
        throw new Exception('O item já está nesse local.');
    }

    // 🚫 Evita sobrescrever
    if (file_exists($novo_abs)) {
        throw new Exception('Já existe um item com esse nome no destino.');
    }

    // ===============================
    // 🚚 MOVE FÍSICO
    // ===============================

    if (!@rename($origem_abs, $novo_abs)) {
        throw new Exception('Erro ao mover o item no sistema de arquivos.');
    }

    // ===============================
    // 💾 ATUALIZA BANCO
    // ===============================

    $stmt = $mysqli->prepare("
        UPDATE silo_arquivos 
        SET caminho_arquivo = ?, 
            parent_id = ?, 
            atualizado_em = NOW()
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param("siii", $novo_caminho_rel, $novo_parent_id, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => '📦 Item movido com sucesso!',
        'novo_caminho' => $novo_caminho_rel,
        'novo_parent_id' => $novo_parent_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}