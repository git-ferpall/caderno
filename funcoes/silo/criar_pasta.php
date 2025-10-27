<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // 🔐 Identifica o usuário
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }

    // 🧾 Nome da pasta
    $nome = trim($_POST['nome'] ?? '');
    if ($nome === '' || preg_match('/[\/\\\\:*?"<>|]/', $nome)) {
        throw new Exception('Nome da pasta inválido');
    }

    // Pasta pai (para subpastas)
    $parent_id = $_POST['parent_id'] ?? '';
    $pastaBase = '/var/www/html/uploads/silo';
    if (!is_dir($pastaBase)) {
        if (!mkdir($pastaBase, 0775, true)) {
            throw new Exception('Falha ao criar diretório base.');
        }
    }

    // Caminho do usuário
    $pastaUsuario = $pastaBase . '/' . $user_id;
    if (!is_dir($pastaUsuario)) {
        if (!mkdir($pastaUsuario, 0775, true)) {
            throw new Exception('Falha ao criar pasta do usuário');
        }
    }

    // 📂 Caminho final (raiz ou subpasta)
    if ($parent_id !== '') {
        // Busca caminho da pasta pai
        $stmt = $mysqli->prepare("SELECT caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ? AND tipo = 'pasta'");
        $stmt->bind_param('ii', $parent_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            throw new Exception('Pasta pai não encontrada');
        }

        $pastaFinal = $pastaBase . '/' . $res['caminho_arquivo'] . '/' . $nome;
    } else {
        $pastaFinal = $pastaUsuario . '/' . $nome;
    }

    // Cria pasta física
    if (!mkdir($pastaFinal, 0775, true)) {
        throw new Exception('Falha ao criar pasta física');
    }

    // Caminho relativo
    $caminhoRelativo = str_replace($pastaBase . '/', '', $pastaFinal);

    // 🔢 Registra no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem)
        VALUES (?, ?, 'folder', 0, ?, ?, 'pasta', 'upload')
    ");
    $stmt->bind_param('issi', $user_id, $nome, $caminhoRelativo, $parent_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        throw new Exception('Erro ao registrar no banco');
    }

    echo json_encode([
        'ok' => true,
        'msg' => '📁 Pasta criada com sucesso!',
        'path' => $caminhoRelativo
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
