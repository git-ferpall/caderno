<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 📦 mover_arquivo.php
 * Move arquivos ou pastas entre diretórios dentro do silo do usuário.
 */

try {
    // 🔒 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $destino = trim($_POST['destino'] ?? '');

    if ($id <= 0) throw new Exception('Parâmetro ID inválido.');

    // Caminho base físico e raiz do usuário
    $base = "/var/www/html/uploads/silo/$user_id";

    // 🏠 Se destino for vazio, 0 ou "raiz" → volta para a raiz do usuário
    if ($destino === '' || $destino === '0' || $destino === 0 || strtolower($destino) === 'raiz') {
        $destino_abs = $base;
        $destino_rel = "silo/$user_id";
    } else {
        // Normaliza destino
        $destino = preg_replace('#^silo/' . $user_id . '/?#', '', $destino);
        $destino = trim($destino, '/');
        $destino_rel = "silo/$user_id/$destino";
        $destino_abs = "/var/www/html/uploads/$destino_rel";
    }

    // 🔍 Valida destino físico
    if (!is_dir($destino_abs)) {
        throw new Exception("Destino inválido ou inexistente: $destino_rel");
    }

    // 🔎 Busca o item
    $stmt = $mysqli->prepare("SELECT id, nome_arquivo, caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('Item não encontrado.');

    $origem_abs = "/var/www/html/uploads/" . $item['caminho_arquivo'];
    if (!file_exists($origem_abs)) throw new Exception('Arquivo/pasta física não encontrada.');

    // 🧭 Monta novo caminho
    $novo_nome = basename($item['caminho_arquivo']);
    $novo_caminho_rel = rtrim($destino_rel, '/') . '/' . $novo_nome;
    $novo_abs = "/var/www/html/uploads/" . $novo_caminho_rel;

    // 🚫 Evita mover para o mesmo local (comparação sem realpath)
    $origem_norm = str_replace('//', '/', $origem_abs);
    $novo_norm   = str_replace('//', '/', $novo_abs);

    if ($origem_norm === $novo_norm) {
        // ✅ Já está no local → retorna sucesso, não erro
        echo json_encode([
            'ok' => true,
            'msg' => '📦 O item já está nesse local.',
            'novo_caminho' => $novo_caminho_rel,
            'destino' => $destino_rel
        ]);
        exit;
    }

    // 🚚 Move fisicamente
    if (!@rename($origem_abs, $novo_abs)) {
        throw new Exception('Erro ao mover o item no sistema de arquivos.');
    }

    // 💾 Atualiza caminho no banco
    $stmt = $mysqli->prepare("
        UPDATE silo_arquivos 
        SET caminho_arquivo = ?, pasta = ?, atualizado_em = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ssii", $novo_caminho_rel, $destino_rel, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 🧩 Retorno completo para o front sincronizar
    echo json_encode([
        'ok' => true,
        'msg' => '📦 Item movido com sucesso!',
        'novo_caminho' => $novo_caminho_rel,
        'destino' => $destino_rel
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
