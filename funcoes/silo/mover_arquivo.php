<?php
require_once __DIR__ . '/funcoes_silo.php';
require_once __DIR__ . '/silo_validacao.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $payload = verify_jwt();
    $user_id = (int)($payload['sub'] ?? ($_SESSION['user_id'] ?? 0));
    if ($user_id <= 0) {
        throw new Exception('unauthorized');
    }

    $id = (int)($_POST['id'] ?? 0);
    $destino_id = (int)($_POST['destino_id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID inválido.');
    }

    $base = realpath(__DIR__ . '/../../uploads');
    if ($base === false) {
        throw new Exception('Base de uploads inválida.');
    }

    $baseUser = $base . '/silo/' . $user_id;
    if (!is_dir($baseUser)) {
        throw new Exception('Diretório base não encontrado.');
    }

    $stmt = $mysqli->prepare("
        SELECT id, nome_arquivo, caminho_arquivo, tipo, parent_id
        FROM silo_arquivos
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        throw new Exception('Item não encontrado.');
    }

    if ($destino_id === $id) {
        throw new Exception('Não é possível mover um item para ele mesmo.');
    }

    $caminhoRel = trim(str_replace(['uploads/', './'], '', (string)$item['caminho_arquivo']), '/');
    $origemAbs = $base . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoRel);

    if (!file_exists($origemAbs) || !siloCaminhoDentroDeBase($base, $origemAbs)) {
        throw new Exception('Arquivo físico não encontrado.');
    }

    if ($destino_id > 0) {
        $stmt = $mysqli->prepare("
            SELECT id, caminho_arquivo
            FROM silo_arquivos
            WHERE id = ? AND user_id = ? AND tipo = 'pasta'
            LIMIT 1
        ");
        $stmt->bind_param('ii', $destino_id, $user_id);
        $stmt->execute();
        $dest = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$dest || empty($dest['caminho_arquivo'])) {
            throw new Exception('Pasta destino não encontrada.');
        }

        $destRel = trim(str_replace(['uploads/', './'], '', $dest['caminho_arquivo']), '/');
        $destinoAbs = $base . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destRel);

        if (!is_dir($destinoAbs) || !siloCaminhoDentroDeBase($base, $destinoAbs)) {
            throw new Exception('Diretório físico da pasta destino não existe.');
        }

        if (str_starts_with($destRel . '/', $caminhoRel . '/') && $item['tipo'] === 'pasta') {
            throw new Exception('Não é possível mover uma pasta para dentro dela mesma.');
        }

        $novo_parent_id = $destino_id;
        $novoCaminhoRel = $destRel . '/' . basename($caminhoRel);
    } else {
        $destinoAbs = $baseUser;
        $novo_parent_id = 0;
        $novoCaminhoRel = 'silo/' . $user_id . '/' . basename($caminhoRel);
    }

    $novoAbs = $destinoAbs . DIRECTORY_SEPARATOR . basename($caminhoRel);

    if (realpath($origemAbs) === realpath($novoAbs)) {
        throw new Exception('O item já está nesse local.');
    }

    if (file_exists($novoAbs)) {
        throw new Exception('Já existe um item com esse nome no destino.');
    }

    if (!rename($origemAbs, $novoAbs)) {
        throw new Exception('Erro ao mover o item.');
    }

    $stmt = $mysqli->prepare("
        UPDATE silo_arquivos
        SET caminho_arquivo = ?, parent_id = ?, atualizado_em = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param('siii', $novoCaminhoRel, $novo_parent_id, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    if ($item['tipo'] === 'pasta') {
        $prefixoAntigo = rtrim($caminhoRel, '/\\') . '/';
        $prefixoNovo = rtrim($novoCaminhoRel, '/\\') . '/';

        $stmt = $mysqli->prepare("
            UPDATE silo_arquivos
            SET caminho_arquivo = REPLACE(caminho_arquivo, ?, ?)
            WHERE user_id = ?
              AND id <> ?
              AND caminho_arquivo LIKE CONCAT(?, '%')
        ");
        $stmt->bind_param('ssiis', $prefixoAntigo, $prefixoNovo, $user_id, $id, $prefixoAntigo);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Item movido com sucesso!',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
