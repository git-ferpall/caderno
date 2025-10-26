<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

/**
 * 🧮 Retorna o uso atual do Silo de Dados (em GB e %)
 * Baseado no total de arquivos do usuário (user_id)
 */
function getSiloUso($mysqli, $user_id)
{
    // 🔹 Recupera payload JWT para pegar armazenamento direto
    $payload = verify_jwt();
    $limite_gb = (float)($payload['armazenamento'] ?? 5.00);

    // 🔹 Soma total dos arquivos do usuário
    $stmt = $mysqli->prepare("SELECT SUM(tamanho_bytes) AS total_bytes FROM silo_arquivos WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $usado_bytes = (float)($row['total_bytes'] ?? 0);
    $usado_gb = $usado_bytes / (1024 * 1024 * 1024);
    $percent = ($limite_gb > 0) ? ($usado_gb / $limite_gb) * 100 : 0;

    return [
        'ok' => true,
        'usado' => round($usado_gb, 3),
        'limite' => round($limite_gb, 2),
        'percent' => round($percent, 1)
    ];
}


/**
 * 📋 Retorna todos os arquivos do usuário logado
 */
function listarArquivos($mysqli, $user_id)
{
    $stmt = $mysqli->prepare("SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, criado_em 
                              FROM silo_arquivos 
                              WHERE user_id = ? 
                              ORDER BY criado_em DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $arquivos = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return ['ok' => true, 'arquivos' => $arquivos];
}

/**
 * 💾 Salva o upload de arquivo no banco
 */
function salvarArquivo($mysqli, $user_id, $nome_final, $tipo, $tamanho, $origem)
{
    $stmt = $mysqli->prepare("INSERT INTO silo_arquivos 
        (user_id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, criado_em) 
        VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issis", $user_id, $nome_final, $tipo, $tamanho, $origem);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * 🗑️ Exclui arquivo físico e registro do banco
 */
function excluirArquivo($mysqli, $user_id, $arquivo_id)
{
    $stmt = $mysqli->prepare("SELECT nome_arquivo FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $arquivo_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $arq = $res->fetch_assoc();
    $stmt->close();

    if (!$arq) return ['ok' => false, 'err' => 'arquivo não encontrado'];

    $path = __DIR__ . '/../../../uploads/silo/' . $arq['nome_arquivo'];
    if (file_exists($path)) unlink($path);

    $stmt = $mysqli->prepare("DELETE FROM silo_arquivos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $arquivo_id, $user_id);
    $stmt->execute();
    $stmt->close();

    return ['ok' => true];
}
