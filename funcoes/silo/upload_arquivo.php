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

    // 🧾 Verifica se há arquivo válido
    if (empty($_FILES['arquivo']['tmp_name']) || !is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
        throw new Exception('nenhum_arquivo');
    }

    $arquivo = $_FILES['arquivo'];
    $origem = $_POST['origem'] ?? 'upload';
    $parent_id = $_POST['parent_id'] ?? null;

    // ⚠️ Verificação de tamanho (evita DoS)
    $max_tamanho = 50 * 1024 * 1024; // 50 MB
    if ($arquivo['size'] > $max_tamanho) {
        throw new Exception('arquivo_muito_grande');
    }

    // 📦 Diretórios base
    $base = realpath(__DIR__ . '/../../uploads');
    $pasta_user = "$base/silo/$user_id";
    if (!is_dir($pasta_user) && !mkdir($pasta_user, 0775, true)) {
        throw new Exception('mkdir_falhou');
    }

    // 📁 Caminho da pasta atual
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
            $rel = str_replace(['uploads/', './'], '', $res['caminho_arquivo']);
            $destinoDir = $base . '/' . $rel;
            $caminhoRelativoBase = $rel;
        }
    }

    // 🧱 Garantir pasta existente
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0775, true)) {
        throw new Exception('mkdir_destino_falhou');
    }

    // 🧹 Sanitiza nome do arquivo
    $nomeOriginal = basename($arquivo['name']);
    $nomeOriginal = preg_replace('/[^a-zA-Z0-9._ -]/', '_', $nomeOriginal);

    // 🚫 Bloqueia extensões proibidas
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $extensoes_bloqueadas = ['php', 'phtml', 'exe', 'sh', 'js', 'html', 'htm', 'bat', 'cmd'];
    if (in_array($extensao, $extensoes_bloqueadas)) {
        throw new Exception('extensao_proibida');
    }

    // 🔍 Detecta MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    // 🧠 Tipos permitidos
    $permitidos = [
        'image/jpeg', 'image/png', 'image/jpg',
        'application/pdf', 'text/plain'
    ];
    if (!in_array($mime_real, $permitidos)) {
        throw new Exception('mime_invalido: ' . $mime_real);
    }

    // 🦠 Verificação de conteúdo suspeito
    $conteudo = file_get_contents($arquivo['tmp_name'], false, null, 0, 512);
    if (preg_match('/<\?(php|=)|base64_decode|shell_exec|eval\(|system\(|passthru\(/i', $conteudo)) {
        throw new Exception('arquivo_malicioso');
    }

    // 🧪 Verificação com ClamAV
    $cmd = "clamdscan --no-summary " . escapeshellarg($arquivo['tmp_name']) . " 2>&1";
    $output = shell_exec($cmd);
    if (!$output) {
        $cmd = "clamscan --no-summary " . escapeshellarg($arquivo['tmp_name']) . " 2>&1";
        $output = shell_exec($cmd);
    }

    if (stripos($output, 'FOUND') !== false) {
        @unlink($arquivo['tmp_name']);
        throw new Exception('arquivo_infectado');
    }

    // 🧾 Nome final e caminho
    $nome_unico = uniqid('', true) . '-' . $nomeOriginal;
    $destino = "$destinoDir/$nome_unico";
    $caminho_relativo = "$caminhoRelativoBase/$nome_unico";

    // 💾 Move arquivo físico
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception('falha_upload');
    }
    chmod($destino, 0644);

    // 📏 Info do arquivo
    $tamanho = filesize($destino);

    // 💽 Grava no banco
    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, origem, tipo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'arquivo', NOW())
    ");
    $pid = !empty($parent_id) ? intval($parent_id) : null;
    $stmt->bind_param('issisis', $user_id, $nomeOriginal, $mime_real, $tamanho, $caminho_relativo, $pid, $origem);
    $stmt->execute();
    $novo_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => '✅ Arquivo enviado e verificado com sucesso!',
        'id' => $novo_id,
        'nome' => $nomeOriginal,
        'tipo' => $mime_real,
        'tamanho' => $tamanho,
        'path' => $caminho_relativo
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
