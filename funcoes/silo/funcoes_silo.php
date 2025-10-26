<?php
require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

/**
 * 📁 Retorna o diretório base do usuário no silo
 */
function getUserSiloDir($user_id) {
    $path = __DIR__ . "/../../uploads/$user_id";
    if (!is_dir($path)) mkdir($path, 0775, true);
    return $path;
}

/**
 * 📦 Retorna informações de uso do armazenamento (sem acessar cliente)
 */
function getSiloUso($mysqli, $user_id) {
    // 🔹 Limite fixo padrão (GB) — no futuro pode vir do JWT
    $limite_gb = 1.00;
    $limite_bytes = $limite_gb * 1024 * 1024 * 1024;

    $user_dir = getUserSiloDir($user_id);
    $total_usado = 0;

    if (is_dir($user_dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($user_dir));
        foreach ($files as $f) {
            if ($f->isFile()) $total_usado += $f->getSize();
        }
    }

    return [
        'ok'          => true,
        'usado_bytes' => $total_usado,
        'usado_gb'    => round($total_usado / (1024 * 1024 * 1024), 2),
        'limite_gb'   => $limite_gb,
        'percent'     => min(round(($total_usado / $limite_bytes) * 100, 1), 100)
    ];
}

/**
 * 📋 Lista arquivos do silo
 */
function listarArquivosSilo($mysqli, $user_id) {
    $stmt = $mysqli->prepare("
        SELECT id, nome_arquivo, tipo_arquivo, tamanho_bytes, origem, criado_em 
        FROM silo_arquivos 
        WHERE user_id = ? 
        ORDER BY criado_em DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}
