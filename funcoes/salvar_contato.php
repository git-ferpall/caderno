<?php
require_once __DIR__ . '/../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../sso/verify_jwt.php';
require_once __DIR__ . '/../configuracao/usuarios_local.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
$payload = verify_jwt();
$user_id = $_SESSION['user_id'] ?? ($payload['sub'] ?? null);
$func_id = (int)($payload['func_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

usuariosEnsureSchema($mysqli);

$nome  = trim($_POST['pfnome'] ?? '');
$email = trim($_POST['pfemail'] ?? '');
$tel   = trim($_POST['pfnum1'] ?? '');
$aceita_email = isset($_POST['aceita_email']) ? 1 : 0;
$aceita_sms   = isset($_POST['aceita_sms']) ? 1 : 0;

if ($nome === '' && $email === '' && $tel === '') {
    echo json_encode(['ok' => false, 'msg' => 'Nenhum dado informado.']);
    exit;
}

if ($func_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT aceita_email, aceita_sms
        FROM usuarios_caderno
        WHERE id = ? AND conta_pai IS NOT NULL
        LIMIT 1
    ");
    $stmt->bind_param('i', $func_id);
    $stmt->execute();
    $atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$atual) {
        echo json_encode(['ok' => false, 'msg' => 'Funcionário não encontrado.']);
        exit;
    }

    $mudouConsentimento = ((int)$atual['aceita_email'] !== $aceita_email)
        || ((int)$atual['aceita_sms'] !== $aceita_sms);

    try {
        if ($mudouConsentimento) {
            $stmt = $mysqli->prepare("
                UPDATE usuarios_caderno
                SET nome = ?, email = ?, telefone = ?, aceita_email = ?, aceita_sms = ?, consentimento_contato_em = NOW()
                WHERE id = ? AND conta_pai IS NOT NULL
            ");
        } else {
            $stmt = $mysqli->prepare("
                UPDATE usuarios_caderno
                SET nome = ?, email = ?, telefone = ?, aceita_email = ?, aceita_sms = ?
                WHERE id = ? AND conta_pai IS NOT NULL
            ");
        }
        $stmt->bind_param('sssiii', $nome, $email, $tel, $aceita_email, $aceita_sms, $func_id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Dados salvos com sucesso!' : 'Erro ao salvar dados.']);
    } catch (Throwable $e) {
        error_log('Erro salvar_contato (funcionario): ' . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Erro interno.']);
    }
    exit;
}

$stmt = $mysqli->prepare("SELECT aceita_email, aceita_sms FROM contato_cliente WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$atual = $stmt->get_result()->fetch_assoc();
$stmt->close();

$mudouConsentimento = false;
if ($atual) {
    $mudouConsentimento = ((int)$atual['aceita_email'] !== $aceita_email)
        || ((int)$atual['aceita_sms'] !== $aceita_sms);
}

try {
    if ($atual) {
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
        $stmt->bind_param('sssiii', $nome, $email, $tel, $aceita_email, $aceita_sms, $user_id);
    } else {
        $stmt = $mysqli->prepare("
            INSERT INTO contato_cliente (user_id, nome, email, telefone, aceita_email, aceita_sms, consentimento_data)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('isssii', $user_id, $nome, $email, $tel, $aceita_email, $aceita_sms);
    }

    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Dados salvos com sucesso!' : 'Erro ao salvar dados.']);
} catch (Throwable $e) {
    error_log('Erro salvar_contato: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno.']);
}
