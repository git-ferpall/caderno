<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $destino = trim($_POST['destino'] ?? '');

    if ($id <= 0)
        throw new Exception('Parâmetro ID inválido.');

    // 🏠 Tratamento especial: mover para a raiz do usuário
    if ($destino === '' || strtolower($destino) === 'raiz') {
        $destino = "silo/$user_id";
    }

    // 🔒 Caminho base e validações
    $base = "/var/www/html/uploads";
    $destino_abs = "$base/$destino";
    if (!is_dir($destino_abs)) {
        throw new Exception("Destino inválido ou inexistente: $destino");
    }

    // 🔍 Busca o arquivo/pasta atual no banco
    $stmt = $mysqli->prepare("SELECT * FROM silo_arquivos WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('Item não encontrado.');

    $origem_abs = "$base/{$item['caminho_arquivo']}";
    if (!file_exists($origem_abs)) throw new Exception('Arquivo/pasta física não encontrada.');

    // 🧭 Monta novo caminho e nome final
    $novo_caminho = rtrim($destino, '/') . '/' . basename($item['caminho_arquivo']);
    $novo_abs = "$base/$novo_caminho";

    // Evita mover para o mesmo local
    if (realpath($origem_abs) === realpath($novo_abs)) {
        throw new Exception('O item já está nesse local.');
    }

    // 🚚 Move arquivo/pasta
    if (!rename($origem_abs, $novo_abs)) {
        throw new Exception('Erro ao mover o item no sistema de arquivos.');
    }

    // 🗃️ Atualiza o caminho no banco
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET caminho_arquivo = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_caminho, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => '📦 Item movido com sucesso!']);
}
catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
