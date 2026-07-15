<?php
declare(strict_types=1);

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/env.php';
require_once __DIR__ . '/../../configuracao/usuarios_local.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

function frutibankJson(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function frutibankEnsureSchema(mysqli $mysqli): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS frutibank_usuarios (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            habilitado_por INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS frutibank_config (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            chave_pix VARCHAR(140) NOT NULL,
            tipo_chave ENUM('cpf','cnpj','email','telefone','aleatoria') NOT NULL DEFAULT 'aleatoria',
            nome_recebedor VARCHAR(25) NOT NULL,
            cidade VARCHAR(15) NOT NULL,
            atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS frutibank_clientes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            nome VARCHAR(255) NOT NULL,
            cpf_cnpj VARCHAR(14) NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_frutibank_clientes_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS frutibank_cobrancas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            cliente_id INT UNSIGNED NOT NULL,
            valor DECIMAL(12,2) NOT NULL,
            descricao VARCHAR(140) NULL,
            vencimento DATE NULL,
            txid VARCHAR(25) NOT NULL,
            payload TEXT NOT NULL,
            status ENUM('pendente','pago','cancelada') NOT NULL DEFAULT 'pendente',
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_frutibank_cobrancas_user (user_id),
            KEY idx_frutibank_cobrancas_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/** Frutibank liberado para este usuário? (admins têm acesso sempre) */
function frutibankHabilitado(mysqli $mysqli, int $user_id): bool
{
    frutibankEnsureSchema($mysqli);
    if (usuarioPerfil($mysqli, $user_id) === 'admin') return true;

    $stmt = $mysqli->prepare('SELECT 1 FROM frutibank_usuarios WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

/** Autentica e exige Frutibank liberado. Retorna o user_id. */
function frutibankRequireAcesso(mysqli $mysqli): int
{
    $payload = verify_jwt(); // sai com 401 se token inválido
    $user_id = (int)($payload['sub'] ?? 0);
    if ($user_id <= 0) {
        frutibankJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
    }
    if (!frutibankHabilitado($mysqli, $user_id)) {
        frutibankJson(['ok' => false, 'msg' => 'Frutibank não liberado para este usuário. Fale com o administrador.'], 403);
    }
    return $user_id;
}

/** Config do recebedor (chave PIX) ou null. */
function frutibankGetConfig(mysqli $mysqli, int $user_id): ?array
{
    frutibankEnsureSchema($mysqli);
    $stmt = $mysqli->prepare('SELECT * FROM frutibank_config WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
