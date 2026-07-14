<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/apontamento_arquivos.php';
require_once __DIR__ . '/silo/silo_validacao.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    $payload = verify_jwt();
    $user_id = (int)($payload['sub'] ?? 0);
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    if ($acao === 'listar') {
        $apontamento_id = (int)($_GET['apontamento_id'] ?? 0);
        if ($apontamento_id <= 0) {
            throw new InvalidArgumentException('ID inválido');
        }
        if (!apontamentoPertenceUsuario($mysqli, $apontamento_id, $user_id)) {
            throw new InvalidArgumentException('Apontamento não encontrado');
        }
        $arquivos = listarArquivosApontamento($mysqli, $apontamento_id, $user_id);
        echo json_encode(['ok' => true, 'arquivos' => $arquivos], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($acao === 'vincular') {
        $apontamento_id = (int)($_POST['apontamento_id'] ?? 0);
        $silo_id = (int)($_POST['silo_arquivo_id'] ?? 0);
        vincularArquivoApontamento($mysqli, $apontamento_id, $silo_id, $user_id);
        echo json_encode(['ok' => true, 'msg' => 'Arquivo vinculado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($acao === 'desvincular') {
        $vinculo_id = (int)($_POST['vinculo_id'] ?? 0);
        desvincularArquivoApontamento($mysqli, $vinculo_id, $user_id);
        echo json_encode(['ok' => true, 'msg' => 'Anexo removido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($acao === 'upload') {
        $apontamento_id = (int)($_POST['apontamento_id'] ?? 0);
        if ($apontamento_id <= 0 || empty($_FILES['arquivo'])) {
            throw new InvalidArgumentException('Dados inválidos');
        }
        if (!apontamentoPertenceUsuario($mysqli, $apontamento_id, $user_id)) {
            throw new InvalidArgumentException('Apontamento não encontrado');
        }

        $validado = siloValidarArquivoUpload($_FILES['arquivo']);
        siloVerificarQuota($mysqli, $user_id, $validado['tamanho']);

        $base = realpath(__DIR__ . '/../uploads');
        if ($base === false) {
            throw new RuntimeException('uploads_indisponivel');
        }

        $pasta_user = $base . '/silo/' . $user_id;
        if (!is_dir($pasta_user) && !mkdir($pasta_user, 0755, true)) {
            throw new RuntimeException('falha_pasta');
        }

        $destino = $pasta_user . DIRECTORY_SEPARATOR . $validado['nome_armazenamento'];
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            throw new RuntimeException('falha_upload');
        }
        chmod($destino, 0644);

        $caminho = "silo/$user_id/" . $validado['nome_armazenamento'];
        $tamanho = filesize($destino) ?: $validado['tamanho'];

        $stmt = $mysqli->prepare("
            INSERT INTO silo_arquivos
            (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, origem, tipo, criado_em)
            VALUES (?, ?, ?, ?, ?, 0, 'apontamento', 'arquivo', NOW())
        ");
        $stmt->bind_param(
            'issis',
            $user_id,
            $validado['nome_original'],
            $validado['mime'],
            $tamanho,
            $caminho
        );
        $stmt->execute();
        $silo_id = (int)$stmt->insert_id;
        $stmt->close();

        vincularArquivoApontamento($mysqli, $apontamento_id, $silo_id, $user_id);

        echo json_encode([
            'ok' => true,
            'msg' => 'Arquivo anexado com sucesso.',
            'arquivo' => [
                'id' => $silo_id,
                'nome_arquivo' => $validado['nome_original'],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new InvalidArgumentException('Ação inválida');
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => caderno_erro_msg($e)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro ao processar anexo.'], JSON_UNESCAPED_UNICODE);
}
