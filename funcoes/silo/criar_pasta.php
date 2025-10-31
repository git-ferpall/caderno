<?php
require_once __DIR__ . '/funcoes_silo.php';
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // 🔐 Autentica o usuário
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

    // 📂 Pasta pai (para subpastas)
    $parent_id = isset($_POST['parent_id']) && is_numeric($_POST['parent_id'])
        ? intval($_POST['parent_id']) : 0;

    // Caminhos base
    $pastaBase = realpath(__DIR__ . '/../../uploads');
    if (!$pastaBase) throw new Exception('Caminho base inválido');

    $pastaSilo = "$pastaBase/silo";
    $pastaUsuario = "$pastaSilo/$user_id";

    // ✅ Garante que os diretórios base existam
    if (!is_dir($pastaSilo)) mkdir($pastaSilo, 0775, true);
    if (!is_dir($pastaUsuario)) mkdir($pastaUsuario, 0775, true);

    // 📁 Determina caminho final
    $destinoDir = $pastaUsuario;
    $caminhoRelativo = "silo/$user_id";

    if ($parent_id > 0) {
        // Busca caminho da pasta pai
        $stmt = $mysqli->prepare("
            SELECT caminho_arquivo 
            FROM silo_arquivos 
            WHERE id = ? AND user_id = ? AND tipo_arquivo = 'folder'
        ");
        $stmt->bind_param('ii', $parent_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res || empty($res['caminho_arquivo'])) {
            throw new Exception('Pasta pai não encontrada');
        }

        // 🔧 Caminho relativo completo da pasta pai
        $rel = str_replace(['uploads/', './'], '', $res['caminho_arquivo']);
        $destinoDir = "$pastaBase/$rel";
        $caminhoRelativo = $rel;
    }

    // 🚀 Cria nova pasta
    $pastaFinal = "$destinoDir/$nome";
    if (!mkdir($pastaFinal, 0775, true) && !is_dir($pastaFinal)) {
        throw new Exception('Falha ao criar pasta física');
    }

    // 💾 Caminho relativo final para salvar no banco
    $caminhoFinal = "$caminhoRelativo/$nome";

    $stmt = $mysqli->prepare("
        INSERT INTO silo_arquivos 
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, caminho_arquivo, parent_id, tipo, origem)
        VALUES (?, ?, 'folder', 0, ?, ?, 'pasta', 'upload')
    ");
    $stmt->bind_param('issi', $user_id, $nome, $caminhoFinal, $parent_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) throw new Exception('Erro ao registrar no banco');

    echo json_encode([
        'ok' => true,
        'msg' => '📁 Pasta criada com sucesso!',
        'path' => $caminhoFinal
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'err' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
