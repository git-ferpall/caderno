<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/funcoes_silo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    if (empty($_FILES['arquivo'])) throw new Exception('nenhum_arquivo');
    $arquivo = $_FILES['arquivo'];
    $origem  = $_POST['origem'] ?? 'upload';

    // 🧮 Verifica limite antes de aceitar upload
    $uso = getSiloUso($mysqli, $user_id);
    if ($uso['usado'] >= $uso['limite']) {
        throw new Exception('limite_excedido');
    }

    // 🧱 Diretório do usuário
    $pasta = __DIR__ . "/../../../uploads/silo/$user_id";
    if (!is_dir($pasta)) {
        if (!mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            throw new Exception('erro_criar_pasta');
        }
    }

    // 🧩 Nome único
    $nome_original = basename($arquivo['name']);
    $ext = pathinfo($nome_original, PATHINFO_EXTENSION);
    $nome_unico = uniqid('', true) . '-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nome_original);

    $caminho_fisico = "$pasta/$nome_unico";
    $caminho_relativo = "uploads/silo/$user_id/$nome_unico";

    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_fisico)) {
        throw new Exception('falha_upload');
    }

    $tipo = mime_content_type($caminho_fisico);
    $tamanho = filesize($caminho_fisico);

    // 💾 Grava no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, origem)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ississ", $user_id, $nome_original, $tipo, $tamanho, $caminho_relativo, $origem);
    $stmt->execute();

    echo json_encode(['ok' => true, 'id' => $stmt->insert_id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
