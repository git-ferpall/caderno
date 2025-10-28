<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 📁 Verifica upload
    if (empty($_FILES['arquivo']['tmp_name']) || !is_uploaded_file($_FILES['arquivo']['tmp_name']))
        throw new Exception('nenhum_arquivo');

    $arquivo   = $_FILES['arquivo'];
    $origem    = $_POST['origem'] ?? 'upload';
    $parent_id = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

    // ==============================
    // 🗂️ Caminhos base
    // ==============================
    $base = realpath(__DIR__ . '/../../uploads');
    $pasta_silo = "$base/silo";
    $pasta_user = "$pasta_silo/$user_id";

    if (!is_dir($pasta_user)) mkdir($pasta_user, 0775, true);

    // Diretório padrão (raiz do usuário)
    $destinoDir = $pasta_user;
    $caminhoRelativoBase = "silo/$user_id";

    // ==============================
    // 📂 Caso o upload seja dentro de uma pasta
    // ==============================
    if ($parent_id > 0) {
        $stmt = $mysqli->prepare("
            SELECT caminho_arquivo 
            FROM silo_arquivos 
            WHERE id = ? AND user_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param('ii', $parent_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && !empty($res['caminho_arquivo'])) {
            // 🔧 Remove prefixos e normaliza
            $rel = trim(str_replace(['uploads/', './'], '', $res['caminho_arquivo']), '/');
            $destinoDir = "$base/$rel";
            $caminhoRelativoBase = $rel;

            // Garante que existe
            if (!is_dir($destinoDir)) mkdir($destinoDir, 0775, true);
        }
    }

    // ==============================
    // 🔒 Segurança — extensões e MIME
    // ==============================
    $nomeOriginal = basename($arquivo['name']);
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

    $bloqueadas = ['php','phtml','exe','sh','js','bat','cmd','html','htm'];
    if (in_array($ext, $bloqueadas))
        throw new Exception('extensao_proibida');

    // MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    $permitidos = ['image/jpeg','image/png','application/pdf','text/plain'];
    if (!in_array($mime, $permitidos))
        throw new Exception('mime_invalido: ' . $mime);

    // 🚫 Verifica assinatura suspeita
    $head = file_get_contents($arquivo['tmp_name'], false, null, 0, 512);
    if (preg_match('/<\?(php|=)|base64_decode|eval\(|shell_exec|system\(/i', $head))
        throw new Exception('arquivo_malicioso');

    // ==============================
    // 💾 Move o arquivo
    // ==============================
    $nome_unico = uniqid('', true) . '-' . $nomeOriginal;
    $destino = "$destinoDir/$nome_unico";
    if (!move_uploaded_file($arquivo['tmp_name'], $destino))
        throw new Exception('falha_upload');

    chmod($destino, 0644);
    $tamanho = filesize($destino);
    $caminho_relativo = "$caminhoRelativoBase/$nome_unico";

    // ==============================
    // 🧱 Salva no banco
    // ==============================
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, origem, tipo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'arquivo', NOW())
    ");
    $stmt->bind_param(
        'issisis',
        $user_id,
        $nomeOriginal,
        $mime,
        $tamanho,
        $caminho_relativo,
        $parent_id,
        $origem
    );
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Arquivo enviado!',
        'path' => $caminho_relativo,
        'pasta_destino' => $destinoDir
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
