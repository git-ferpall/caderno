<?php
/**
 * /sso/login_cookie.php
 * Cria sessão local no Caderno com base no retorno da API Frutag.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['id']) || empty($input['nome'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'invalid_data']);
    exit;
}

$_SESSION['user_id'] = $input['id'];
$_SESSION['user_nome'] = $input['nome'];
$_SESSION['user_tipo'] = $input['tipo'] ?? 'U';
$_SESSION['user_ativo'] = $input['ativo'] ?? 'S';

echo json_encode(['ok' => true, 'msg' => 'Sessão criada com sucesso']);
