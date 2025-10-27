<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $destino = trim($_POST['destino'] ?? '');

    if ($id <= 0) {
        throw new Exception('Parâmetro ID inválido.');
    }

    // Caminho base físico
    $base = "/var/www/html/uploads/silo/$user_id";

    // 🏠 Se destino for "Raiz", "0" ou vazio → primeira camada do usuário
    if ($destino === '' || $destino === '0' || $destino === 0 || strtolower($destino) === 'raiz') {
        $destino_abs = $base;
        $destino_rel = "silo/$user_id";
    } else {
        // Caminho normal de pasta
        $destino_rel = "silo/$user_id/" . ltrim($destino, '/');
        $destino_abs = "/var/www/html/uploads/" . $destino_rel;
    }

    if (!is_dir($destino_abs)) {
        throw new Exception("Destino inválido ou inexistente: $destino_rel");
    }

    // 🔍 Busca o item
    $stmt = $mysqli->prepare("SELECT * FROM silo_arquivos WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('Item não encontrado.');

    $origem_abs = "/var/www/html/uploads/" . $item['caminho_arquivo'];
    if (!file_exists($origem_abs)) throw new Exception('Arquivo/pasta física não encontrada.');

    // Evita duplicidade
    $novo_caminho_rel = rtrim($destino_rel, '/') . '/' . basename($item['caminho_arquivo']);
    $novo_abs = "/var/www/html/uploads/" . $novo_caminho_rel;

    if (realpath($origem_abs) === realpath($novo_abs)) {
        throw new Exception('O item já está nesse local.');
    }

    // 🚚 Move fisicamente
    if (!rename($origem_abs, $novo_abs)) {
        throw new Exception('Erro ao mover o item no sistema de arquivos.');
    }

    // 🗃️ Atualiza o banco
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET caminho_arquivo = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_caminho_rel, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => '📦 Item movido com sucesso!']);
}
catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
