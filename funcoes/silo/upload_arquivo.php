<?php
@ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    if (empty($_FILES['arquivo']['name'])) throw new Exception('arquivo_vazio');

    $nome_original = basename($_FILES['arquivo']['name']);
    $tipo = $_FILES['arquivo']['type'];
    $tamanho = $_FILES['arquivo']['size'];
    $origem = $_POST['origem'] ?? 'upload';

    // 🔒 Extensões permitidas
    $permitidos = ['pdf','txt','jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidos)) throw new Exception('tipo_invalido');

    // 📂 Caminho absoluto e fixo
    $base = '/var/www/html/uploads';
    $pasta_silo = "$base/silo";
    $pasta_user = "$pasta_silo/$user_id";

    // 📁 Cria estrutura completa se não existir
    if (!is_dir($pasta_user)) {
        @mkdir($pasta_user, 0775, true);
    }

    // 🧱 Garante que o Apache tenha acesso
    @chown($base, 'www-data');
    @chgrp($base, 'www-data');
    @chmod($base, 0775);
    @chown($pasta_silo, 'www-data');
    @chgrp($pasta_silo, 'www-data');
    @chmod($pasta_silo, 0775);
    @chown($pasta_user, 'www-data');
    @chgrp($pasta_user, 'www-data');
    @chmod($pasta_user, 0775);

    if (!is_writable($pasta_user)) {
        throw new Exception("sem_permissao_em_$pasta_user");
    }

    // 🧩 Gera nome único
    $nome_final = uniqid('', true) . '-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nome_original);
    $destino = "$pasta_user/$nome_final";

    // 🚀 Move o arquivo
    if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
        throw new Exception('falha_upload');
    }

    // 💾 Registra no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, caminho, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('ississ', $user_id, $nome_final, $tipo, $tamanho, $origem, $destino);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => 'upload_sucesso']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
