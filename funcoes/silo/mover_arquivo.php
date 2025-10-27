<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('Usuário não autenticado');

    $id = intval($_POST['id'] ?? 0);
    $destino_id = intval($_POST['destino'] ?? 0);
    if ($id <= 0 || $destino_id <= 0) throw new Exception('Parâmetros inválidos');

    // 🔎 Busca item original
    $stmt = $mysqli->prepare("SELECT * FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) throw new Exception('Item não encontrado');

    // 🔎 Busca pasta de destino
    $stmt = $mysqli->prepare("SELECT caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ? AND tipo = 'pasta'");
    $stmt->bind_param("ii", $destino_id, $user_id);
    $stmt->execute();
    $dest = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$dest) throw new Exception('Pasta de destino inválida');

    // Caminhos
    $base = '/var/www/html/uploads';
    $origem_abs = "$base/{$item['caminho_arquivo']}";
    $novo_caminho_rel = $dest['caminho_arquivo'] . '/' . $item['nome_arquivo'];
    $destino_abs = "$base/$novo_caminho_rel";

    // 🚚 Move fisicamente
    if (!file_exists($origem_abs)) throw new Exception('Arquivo físico não encontrado');
    if (!rename($origem_abs, $destino_abs)) throw new Exception('Falha ao mover arquivo');

    // 🗄️ Atualiza no banco
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET caminho_arquivo = ?, parent_id = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("siii", $novo_caminho_rel, $destino_id, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => '📦 Item movido com sucesso!'], JSON_UNESCAPED_UNICODE);
}
catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
