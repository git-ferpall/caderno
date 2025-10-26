<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // ⚙️ 1️⃣ Verifica limite de armazenamento
    $limite_gb = (float)($payload['armazenamento'] ?? 5.00);
    $uso = getSiloUso($mysqli, $user_id);
    $usado_gb = (float)$uso['usado'];

    // 🚫 Se ultrapassar o limite, bloqueia o upload
    if ($usado_gb >= $limite_gb) {
        echo json_encode([
            'ok' => false,
            'err' => 'limite_atingido',
            'msg' => "Limite de armazenamento de {$limite_gb} GB atingido. Exclua arquivos para liberar espaço."
        ]);
        exit;
    }

    // ⚙️ 2️⃣ Processamento do upload normal
    if (empty($_FILES['arquivo'])) throw new Exception('no_file');
    $file = $_FILES['arquivo'];

    $permitidos = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
    if (!in_array($file['type'], $permitidos)) throw new Exception('tipo_invalido');

    $nome = basename($file['name']);
    $tamanho = (int)$file['size'];
    $origem = $_POST['origem'] ?? 'upload';

    // 📂 pasta de destino por usuário
    $pasta = __DIR__ . "/../../../uploads/silo/{$user_id}";
    if (!is_dir($pasta)) mkdir($pasta, 0775, true);

    $destino = $pasta . "/" . uniqid('', true) . "-" . $nome;
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        throw new Exception('falha_upload');
    }

    // 🧮 Atualiza tabela
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, caminho)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ississ", $user_id, $nome, $file['type'], $tamanho, $origem, $destino);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
