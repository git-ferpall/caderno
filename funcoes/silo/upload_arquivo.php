<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    if (empty($_FILES['arquivo']['tmp_name'])) throw new Exception('nenhum_arquivo');

    $arquivo = $_FILES['arquivo'];
    $origem  = $_POST['origem'] ?? 'upload';

    // Caminho base ABSOLUTO
    $base = '/var/www/html/uploads';
    $pasta_silo = "$base/silo";
    $pasta_user = "$pasta_silo/$user_id";

    // Cria diretórios (recursivo e seguro)
    if (!is_dir($pasta_user)) {
        if (!mkdir($pasta_user, 0775, true) && !is_dir($pasta_user)) {
            throw new Exception('mkdir_falhou: ' . $pasta_user);
        }
    }

    // Verifica tipo permitido
    $permitidos = ['image/jpeg','image/png','image/jpg','application/pdf','text/plain'];
    if (!in_array($arquivo['type'], $permitidos)) throw new Exception('tipo_invalido');

    // Gera nome único e caminho relativo
    $nome_unico = uniqid('', true) . '-' . basename($arquivo['name']);
    $destino = "$pasta_user/$nome_unico";
    $caminho_relativo = "uploads/silo/$user_id/$nome_unico";

    // Move o arquivo
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception('falha_upload');
    }

    // Calcula tamanho
    $tamanho = filesize($destino);

    // Salva registro no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
            (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, origem, criado_em) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('ississ', $user_id, $nome_unico, $arquivo['type'], $tamanho, $caminho_relativo, $origem);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => 'upload_ok']);
} 
catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
