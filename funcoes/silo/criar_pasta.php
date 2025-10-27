<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔐 Verifica autenticação
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? ($_SESSION['user_id'] ?? null);
    if (!$user_id) throw new Exception('Usuário não autenticado.');

    // 📂 Recebe parâmetros
    $nome = trim($_POST['nome'] ?? '');
    $parent_id = trim($_POST['parent_id'] ?? '');

    if ($nome === '') {
        throw new Exception('Nome da pasta inválido.');
    }

    // 🧹 Sanitiza nome da pasta (mantém acentos e espaços)
    $nome = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '', $nome);

    // 🏠 Caminho base
    $base_dir = __DIR__ . "/../../../uploads/silo/{$user_id}";
    if (!is_dir($base_dir)) mkdir($base_dir, 0775, true);

    // 📁 Caminho destino
    $destino = $base_dir;
    if ($parent_id !== '') {
        $destino .= '/' . basename($parent_id);
        if (!is_dir($destino)) throw new Exception('Pasta de destino não encontrada.');
    }

    $nova_pasta = $destino . '/' . $nome;
    if (is_dir($nova_pasta)) throw new Exception('Já existe uma pasta com esse nome.');

    if (!mkdir($nova_pasta, 0775)) throw new Exception('Falha ao criar pasta.');

    echo json_encode([
        'ok' => true,
        'msg' => "Pasta <b>{$nome}</b> criada com sucesso!",
        'path' => str_replace($base_dir, '', $nova_pasta)
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
