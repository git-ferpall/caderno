<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * 🧩 rename_arquivo.php
 * Corrigido para funcionar com subpastas em qualquer nível
 * Corrige prefixos, valida caminhos, e suporta tanto arquivos quanto pastas
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

    // 🧾 Entrada
    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca o item
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo, tipo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) throw new Exception('arquivo_nao_encontrado');

    $nome_antigo = $item['nome_arquivo'];
    $tipo_item = $item['tipo']; // 'arquivo' ou 'pasta'
    $caminho_relativo = trim($item['caminho_arquivo'], '/');

    // 🧭 Monta caminho absoluto — remove prefixos indevidos
    $base_path = realpath(__DIR__ . '/../../uploads');
    $caminho_relativo_limpo = preg_replace('#^uploads/#', '', $caminho_relativo);
    $caminho_antigo = $base_path . '/' . $caminho_relativo_limpo;

    // 🚨 Segurança: impede escapes tipo ../
    $caminho_antigo = realpath($caminho_antigo);
    if (!$caminho_antigo || strpos($caminho_antigo, $base_path) !== 0) {
        elog("⚠️ Caminho inválido ou fora da base: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    if (!file_exists($caminho_antigo)) {
        elog("❌ Caminho inexistente: $caminho_antigo");
        throw new Exception('arquivo_fisico_nao_encontrado');
    }

    // 📁 NOVO NOME — mantém extensão se for arquivo
    if ($tipo_item === 'arquivo') {
        $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);
        if ($extensao && !str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
            $novo_nome .= '.' . $extensao;
        }
    }

    // 📦 Monta novos caminhos
    $novo_caminho_abs = dirname($caminho_antigo) . '/' . $novo_nome;
    $novo_caminho_rel = str_replace($base_path . '/', '', $novo_caminho_abs);

    // 🚫 Verifica duplicidade
    if (file_exists($novo_caminho_abs)) {
        elog("🚫 Já existe: $novo_caminho_abs");
        throw new Exception('arquivo_duplicado');
    }

    // 🚚 Faz rename físico
    if (!@rename($caminho_antigo, $novo_caminho_abs)) {
        elog("💥 Falha ao renomear: $caminho_antigo → $novo_caminho_abs");
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza banco principal
    $stmt = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ssii', $novo_nome, $novo_caminho_rel, $id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 🪄 Se for pasta, atualiza todos os filhos
    if ($tipo_item === 'pasta') {
        $stmt = $mysqli->prepare("UPDATE silo_arquivos SET caminho_arquivo = REPLACE(caminho_arquivo, ?, ?) WHERE user_id = ? AND caminho_arquivo LIKE CONCAT(?, '/%')");
        $stmt->bind_param('ssis', $caminho_relativo, $novo_caminho_rel, $user_id, $caminho_relativo);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['ok' => true, 'msg' => '✅ Renomeado com sucesso!']);

} catch (Throwable $e) {
    elog('Erro: ' . $e->getMessage());
    http_response_code(500);
    $msg = match($e->getMessage()) {
        'arquivo_duplicado' => 'Já existe um item com esse nome.',
        'arquivo_nao_encontrado' => 'Registro não encontrado no banco.',
        'arquivo_fisico_nao_encontrado' => 'Arquivo ou pasta física não encontrada no servidor.',
        'falha_ao_renomear_arquivo' => 'Falha ao renomear. Verifique permissões.',
        'param_invalid' => 'Parâmetros inválidos.',
        'unauthorized' => 'Usuário não autenticado.',
        default => $e->getMessage(),
    };
    echo json_encode(['ok' => false, 'err' => $msg]);
}
