<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $payload = verify_jwt();
    $user_id = $payload['sub'] ?? null;
}

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

$nome  = trim($_POST['pfnome'] ?? '');
$email = trim($_POST['pfemail'] ?? '');
$tel   = trim($_POST['pfnum1'] ?? '');
$aceita_email = isset($_POST['aceita_email']) ? 1 : 0;
$aceita_sms   = isset($_POST['aceita_sms']) ? 1 : 0;

if ($nome === '' && $email === '' && $tel === '') {
    echo json_encode(['ok' => false, 'msg' => 'Nenhum dado informado.']);
    exit;
}

// Busca dados atuais para comparar
$stmt = $mysqli->prepare("SELECT aceita_email, aceita_sms FROM contato_cliente WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$atual = $res->fetch_assoc();
$stmt->close();

$mudouConsentimento = false;
if ($atual) {
    $mudouConsentimento = ($atual['aceita_email'] != $aceita_email) || ($atual['aceita_sms'] != $aceita_sms);
}

try {
    if ($atual) {
        // Atualiza registro existente
        if ($mudouConsentimento) {
            $stmt = $mysqli->prepare("
                UPDATE contato_cliente
                SET nome = ?, email = ?, telefone = ?, aceita_email = ?, aceita_sms = ?, consentimento_data = NOW()
                WHERE user_id = ?
            ");
        } else {
            $stmt = $mysqli->prepare("
                UPDATE contato_cliente
                SET nome = ?, email = ?, telefone = ?, aceita_email = ?, aceita_sms = ?
                WHERE user_id = ?
            ");
        }
        $stmt->bind_param("sssiii", $nome, $email, $tel, $aceita_email, $aceita_sms, $user_id);
    } else {
        // Novo cadastro
        $stmt = $mysqli->prepare("
            INSERT INTO contato_cliente (user_id, nome, email, telefone, aceita_email, aceita_sms, consentimento_data)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssii", $user_id, $nome, $email, $tel, $aceita_email, $aceita_sms);
    }

    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Dados salvos com sucesso!' : 'Erro ao salvar dados.']);
} catch (Throwable $e) {
    error_log("Erro salvar_contato: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno.']);
}
