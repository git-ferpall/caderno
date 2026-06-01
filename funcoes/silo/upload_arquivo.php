<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';
require_once __DIR__ . '/silo_validacao.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    $payload = verify_jwt();
    $user_id = (int)($payload['sub'] ?? ($_SESSION['user_id'] ?? 0));
    if ($user_id <= 0) {
        throw new InvalidArgumentException('unauthorized');
    }

    if (empty($_FILES['arquivo'])) {
        throw new InvalidArgumentException('nenhum_arquivo');
    }

    $validado = siloValidarArquivoUpload($_FILES['arquivo']);
    siloVerificarQuota($mysqli, $user_id, $validado['tamanho']);

    $origem = $_POST['origem'] ?? 'upload';
    $parent_id = isset($_POST['parent_id']) && is_numeric($_POST['parent_id'])
        ? (int)$_POST['parent_id'] : 0;

    $base = realpath(__DIR__ . '/../../uploads');
    if ($base === false) {
        throw new RuntimeException('uploads_indisponivel');
    }

    $pasta_user = $base . '/silo/' . $user_id;
    if (!is_dir($pasta_user) && !mkdir($pasta_user, 0755, true)) {
        throw new RuntimeException('falha_criar_pasta_usuario');
    }

    $destinoDir = $pasta_user;
    $caminhoRelativoBase = "silo/$user_id";

    if ($parent_id > 0) {
        $stmt = $mysqli->prepare("
            SELECT caminho_arquivo, tipo, tipo_arquivo
            FROM silo_arquivos
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $parent_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $isFolder = $res
            && ($res['tipo'] === 'pasta' || strtolower((string)$res['tipo_arquivo']) === 'folder');

        if (!$isFolder || empty($res['caminho_arquivo'])) {
            throw new InvalidArgumentException('pasta_pai_invalida');
        }

        $rel = trim(str_replace(['uploads/', './'], '', $res['caminho_arquivo']), '/');
        $destinoDir = $base . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $caminhoRelativoBase = $rel;

        if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true)) {
            throw new RuntimeException('falha_criar_pasta_destino');
        }

        if (!siloCaminhoDentroDeBase($base, $destinoDir)) {
            throw new InvalidArgumentException('caminho_invalido');
        }
    }

    $destino = $destinoDir . DIRECTORY_SEPARATOR . $validado['nome_armazenamento'];
    if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
        throw new RuntimeException('falha_upload');
    }

    chmod($destino, 0644);
    $tamanho = filesize($destino) ?: $validado['tamanho'];
    $caminho_relativo = $caminhoRelativoBase . '/' . $validado['nome_armazenamento'];

    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, origem, tipo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'arquivo', NOW())
    ");
    $stmt->bind_param(
        'issisis',
        $user_id,
        $validado['nome_original'],
        $validado['mime'],
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
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    $msg = match ($e->getMessage()) {
        'nenhum_arquivo' => 'Nenhum arquivo foi enviado.',
        'upload_falhou' => 'Falha no envio do arquivo.',
        'arquivo_grande' => 'Arquivo excede o limite de 25 MB.',
        'extensao_proibida', 'tipo_nao_permitido', 'conteudo_invalido', 'arquivo_malicioso' =>
            'Tipo de arquivo não permitido ou conteúdo suspeito.',
        'limite_armazenamento' => 'Limite de armazenamento atingido.',
        'pasta_pai_invalida' => 'Pasta de destino inválida.',
        'nome_invalido' => 'Nome de arquivo inválido.',
        'unauthorized' => 'Usuário não autenticado.',
        default => 'Arquivo rejeitado pela validação de segurança.',
    };
    echo json_encode(['ok' => false, 'err' => $msg], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'Erro interno ao processar upload.'], JSON_UNESCAPED_UNICODE);
}
