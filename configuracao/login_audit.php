<?php
declare(strict_types=1);

require_once __DIR__ . '/configuracao_conexao.php';

function loginAuditEnsureSchema(mysqli $mysqli): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            login_hash CHAR(64) NOT NULL,
            ip_hash CHAR(64) NOT NULL,
            sucesso TINYINT(1) NOT NULL DEFAULT 0,
            motivo VARCHAR(50) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_login_attempts_login_time (login_hash, criado_em),
            KEY idx_login_attempts_ip_time (ip_hash, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function loginAuditHashLogin(?string $login): string
{
    if ($login === null || trim($login) === '') {
        return hash('sha256', 'empty');
    }

    return hash('sha256', mb_strtolower(trim($login)));
}

function loginAuditHashIp(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = trim(explode(',', (string) $ip)[0]);

    return hash('sha256', $ip);
}

function loginAuditRecord(?string $login, bool $success, ?string $motivo = null): void
{
    global $mysqli;

    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        return;
    }

    try {
        loginAuditEnsureSchema($mysqli);

        $loginHash = loginAuditHashLogin($login);
        $ipHash = loginAuditHashIp();
        $motivoDb = $motivo !== null && $motivo !== '' ? substr($motivo, 0, 50) : null;
        $sucesso = $success ? 1 : 0;

        $stmt = $mysqli->prepare('
            INSERT INTO login_attempts (login_hash, ip_hash, sucesso, motivo)
            VALUES (?, ?, ?, ?)
        ');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('ssis', $loginHash, $ipHash, $sucesso, $motivoDb);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[caderno] login_audit falhou: ' . $e->getMessage());
    }
}
