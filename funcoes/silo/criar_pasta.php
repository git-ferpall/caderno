<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/funcoes_silo.php';
try {
    // 🔐 Autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('Usuário não autenticado');

    // 🗂️ Dados recebidos
    $nome = trim($_POST['nome'] ?? '');
    $parent_id = trim($_POST['parent_id'] ?? '');

    if ($nome === '') {
        throw new Exception('Nome da pasta inválido');
    }

    // ⚙️ Sanitiza nome (mantém acentos e espaços)
    // Remove apenas caracteres perigosos
    $nome = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '', $nome);

    // Caminho base do usuário
    $base_dir = __DIR__ . "/../../../uploads/silo/{$user_id}";
    if (!is_dir($base_dir)) {
        mkdir($base_dir, 0775, true);
    }

    // Se estiver dentro de uma subpasta
    $pasta_destino = $base_dir;
    if ($parent_id !== '') {
        // Previne diretórios fora da base
        $pasta_destino .= '/' . basename($parent_id);
        if (!is_dir($pasta_destino)) {
            throw new Exception('Pasta de destino não encontrada');
        }
    }

    // Pasta final
    $nova_pasta = $pasta_destino . '/' . $nome;

    if (is_dir($nova_pasta)) {
        throw new Exception('Já existe uma pasta com esse nome');
    }

    // Cria a pasta
    if (!mkdir($nova_pasta, 0775)) {
        throw new Exception('Falha ao criar pasta');
    }

    echo json_encode([
        'ok' => true,
        'msg' => "A pasta <b>{$nome}</b> foi criada com sucesso.",
        'path' => str_replace($base_dir, '', $nova_pasta)
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
