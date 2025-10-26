<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Renomeia arquivos no silo, mantendo extensão, checando duplicidade e atualizando o banco.
 * Compatível com Docker (uploads em /var/www/html/uploads/silo/{user_id})
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔒 Autenticação via JWT
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 🧾 Parâmetros recebidos
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca informações do arquivo atual
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $res['nome_arquivo'];
    $caminho_relativo = $res['caminho_arquivo'];

    // 🧭 Monta caminho absoluto correto
    $base_path = realpath(__DIR__ . '/../../uploads'); // /var/www/html/uploads
    $caminho_antigo = $base_path . '/' . str_replace(['uploads/', './'], '', $caminho_relativo);

    if (!$caminho_antigo || !file_exists($caminho_antigo)) {
        elog("Arquivo físico não encontrado: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 🧩 Mantém a mesma extensão caso o usuário não digite
    $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
    if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
        $novo_nome .= '.' . $extensao;
    }

    // 🧱 Monta novos caminhos (relativo e absoluto)
    $novo_caminho_rel = dirname($caminho_relativo) . '/' . $novo_nome;
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;

    // 🚫 Verifica duplicidade (arquivo já existe)
    if (file_exists($novo_caminho_abs)) {
        elog("Tentativa de renomear para nome duplicado: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🚚 Executa o rename físico
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("Falha ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza banco
    $stmtUp = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmtUp->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode(['ok' => true, 'msg' => 'Arquivo renomeado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um arquivo com esse nome.',
        'arquivo_nao_encontrado' => 'Arquivo não encontrado no banco de dados.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo físico não encontrado no servidor.',
        'falha_ao_renomear_arquivo' => 'Falha ao renomear o arquivo. Verifique permissões.',
        'param_invalid' => 'Parâmetros inválidos.',
        'unauthorized' => 'Usuário não autenticado.',
        default => $e->getMessage(),
    };
    echo json_encode(['ok' => false, 'err' => $msg]);
}
