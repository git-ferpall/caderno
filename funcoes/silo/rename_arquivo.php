<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('unauthorized');

    $id = intval($_POST['id'] ?? 0);
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    if ($id <= 0 || $novo_nome === '') throw new Exception('param_invalid');

    // 🔎 Busca o arquivo atual
    $stmt = $mysqli->prepare("SELECT nome_arquivo, caminho_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if (!$res) throw new Exception('arquivo_nao_encontrado');

    $caminho_antigo = $res['caminho_arquivo'];
    $nome_antigo = $res['nome_arquivo'];
    $extensao = pathinfo($nome_antigo, PATHINFO_EXTENSION);

    // 🔧 Mantém extensão original, caso o usuário não digite
    if (!str_ends_with(strtolower($novo_nome), '.' . strtolower($extensao))) {
        $novo_nome .= '.' . $extensao;
    }

    $novo_caminho = dirname($caminho_antigo) . '/' . $novo_nome;

    // 🚚 Renomeia fisicamente o arquivo
    if (!@rename($caminho_antigo, $novo_caminho)) {
        throw new Exception('falha_ao_renomear_arquivo');
    }

    // 💾 Atualiza no banco
    $stmtUp = $mysqli->prepare("UPDATE silo_arquivos SET nome_arquivo = ?, caminho_arquivo = ? WHERE id = ? AND user_id = ?");
    $stmtUp->bind_param('ssii', $novo_nome, $novo_caminho, $id, $user_id);
    $stmtUp->execute();

    echo json_encode(['ok' => true, 'msg' => 'Arquivo renomeado com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
