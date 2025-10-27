<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Renomeia arquivos e pastas no Silo de Dados
 * Corrige caminhos relativos inconsistentes (com/sem "uploads/").
 * Loga erros para depuração.
 */

$logFile = __DIR__ . '/rename_error.log';
function elog($msg) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('c') . "] $msg\n", FILE_APPEND);
}

try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    // 🧾 Parâmetros recebidos
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔍 Busca o item atual
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo, tipo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $item['nome_arquivo'];
    $caminho_relativo = $item['caminho_arquivo'];
    $tipo_item = $item['tipo']; // 'arquivo' ou 'pasta'

    // 🧭 Monta caminho absoluto (corrige prefixo "uploads/")
    $base_path = realpath(__DIR__ . '/../../uploads');
    $caminho_relativo_limpo = preg_replace('#^/?uploads/#', '', $caminho_relativo);
    $caminho_antigo = $base_path . '/' . $caminho_relativo_limpo;

    if (!file_exists($caminho_antigo)) {
        elog("❌ Arquivo/pasta física não encontrada: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 📁 Renomeia pastas
    if ($tipo_item === 'pasta') {
        $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;
        $novo_caminho_rel = preg_replace('#^' . preg_quote($base_path . '/', '#') . '#', '', $novo_caminho_abs);

        if (file_exists($novo_caminho_abs)) throw new Exception('arquivo_duplicado');
        if (!@rename($caminho_antigo, $novo_caminho_abs)) throw new Exception('falha_ao_renomear_arquivo');

        // Atualiza o banco
        $stmt = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['ok' => true, 'msg' => '📁 Pasta renomeada com sucesso!']);
        exit;
    }

    // 🧩 Renomeia arquivos mantendo extensão
    $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
    if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
        $novo_nome .= '.' . $extensao;
    }

    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;
    $novo_caminho_rel = preg_replace('#^' . preg_quote($base_path . '/', '#') . '#', '', $novo_caminho_abs);

    // 🚫 Já existe outro arquivo com o mesmo nome?
    if (file_exists($novo_caminho_abs)) {
        elog("🚫 Duplicado: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🚚 Executa o rename físico
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("Falha ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza banco
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => '✅ Arquivo renomeado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um arquivo com esse nome.',
        'arquivo_nao_encontrado' => 'Registro não encontrado no banco.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo ou pasta física não encontrada no servidor.',
        'falha_ao_renomear_arquivo' => 'Falha ao renomear. Verifique permissões.',
        'param_invalid' => 'Parâmetros inválidos.',
        'unauthorized' => 'Usuário não autenticado.',
        default => $e->getMessage(),
    };
    echo json_encode(['ok' => false, 'err' => $msg]);
}
