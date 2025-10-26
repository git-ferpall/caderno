<?php
require_once __DIR__ . '/funcoes_silo.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 📦 Verifica limite de armazenamento
    $limite_gb = (float)($payload['armazenamento'] ?? 5.00);
    $uso = getSiloUso($mysqli, $user_id);
    $usado_gb = (float)($uso['usado']);
    if ($usado_gb >= $limite_gb) {
        echo json_encode([
            'ok' => false,
            'err' => 'limite_atingido',
            'msg' => "Limite de {$limite_gb} GB atingido. Exclua arquivos antes de enviar novos."
        ]);
        exit;
    }

    if (empty($_FILES['arquivo'])) throw new Exception('no_file');
    $file = $_FILES['arquivo'];

    $permitidos = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
    if (!in_array($file['type'], $permitidos)) throw new Exception('tipo_invalido');

    $nome = basename($file['name']);
    $tamanho = (int)$file['size'];
    $origem = $_POST['origem'] ?? 'upload';

    // 📂 Cria diretório do usuário
    $pasta = __DIR__ . "/../../../uploads/silo/{$user_id}";
    if (!is_dir($pasta)) mkdir($pasta, 0775, true);

    $arquivo_final = uniqid('', true) . "-" . $nome;
    $destino = $pasta . "/" . $arquivo_final;

    if (!move_uploaded_file($file['tmp_name'], $destino))
        throw new Exception('falha_upload');

    // 💾 Salva no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, caminho, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ississ", $user_id, $nome, $file['type'], $tamanho, $origem, $arquivo_final);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
