<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 📂 Verifica se há arquivo enviado
    if (empty($_FILES['arquivo']['tmp_name'])) throw new Exception('nenhum_arquivo');

    $arquivo = $_FILES['arquivo'];
    $origem  = $_POST['origem'] ?? 'upload';
    $parent_id = $_POST['parent_id'] ?? null;

    // 📦 Diretórios base
    $base = realpath(__DIR__ . '/../../uploads');
    $pasta_silo = "$base/silo";
    $pasta_user = "$pasta_silo/$user_id";

    // 🔧 Garante que o diretório do usuário existe
    if (!is_dir($pasta_user)) {
        if (!mkdir($pasta_user, 0775, true)) {
            throw new Exception('mkdir_falhou: ' . $pasta_user);
        }
    }

    // 📁 Define diretório de destino (pasta atual)
    $destinoDir = $pasta_user;
    $caminhoRelativoBase = "silo/$user_id";

    if (!empty($parent_id)) {
        $stmt = $mysqli->prepare("
            SELECT caminho_arquivo 
            FROM silo_arquivos 
            WHERE id = ? AND user_id = ? AND tipo_arquivo = 'folder'
        ");
        $stmt->bind_param('ii', $parent_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && !empty($res['caminho_arquivo'])) {
            // 🔧 Corrige para evitar "uploads/uploads"
            $rel = str_replace(['uploads/', './'], '', $res['caminho_arquivo']);
            $destinoDir = $base . '/' . $rel;
            $caminhoRelativoBase = $rel;
        }
    }

    // 🔍 Tipos permitidos
    $permitidos = [
        'image/jpeg', 'image/png', 'image/jpg',
        'application/pdf', 'text/plain'
    ];
    if (!in_array($arquivo['type'], $permitidos)) throw new Exception('tipo_invalido');

    // 🧾 Nome final e caminho
    $nomeOriginal = basename($arquivo['name']);
    $nome_unico = uniqid('', true) . '-' . $nomeOriginal;
    $destino = "$destinoDir/$nome_unico";

    // Garante que a pasta exista
    if (!is_dir($destinoDir)) {
        mkdir($destinoDir, 0775, true);
    }

    // Caminho relativo para salvar no banco
    $caminho_relativo = "$caminhoRelativoBase/$nome_unico";

    // 💾 Move o arquivo físico
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception('falha_upload');
    }

    // 📏 Tamanho e tipo
    $tamanho = filesize($destino);
    $tipoMime = $arquivo['type'];

    // 🧱 Salva registro no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
            (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, origem, tipo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'arquivo', NOW())
    ");
    $stmt->bind_param(
        'issisis',
        $user_id,
        $nomeOriginal,
        $tipoMime,
        $tamanho,
        $caminho_relativo,
        $parent_id,
        $origem
    );
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Arquivo enviado com sucesso!',
        'path' => $caminho_relativo
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
