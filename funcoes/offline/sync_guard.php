<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function offlineSyncEnsureSchema(mysqli $mysqli): void
{
    offlineEnsureSchema($mysqli);
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS offline_sync_log (
            client_id VARCHAR(64) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            script VARCHAR(128) NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (client_id),
            KEY idx_offline_sync_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function offlineSyncFindDuplicate(mysqli $mysqli, int $user_id, string $client_id): bool
{
    if ($client_id === '' || strlen($client_id) > 64) {
        return false;
    }
    offlineSyncEnsureSchema($mysqli);
    $stmt = $mysqli->prepare("SELECT 1 FROM offline_sync_log WHERE client_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('si', $client_id, $user_id);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

function offlineSyncRegister(mysqli $mysqli, int $user_id, string $client_id, string $script): void
{
    if ($client_id === '' || strlen($client_id) > 64) {
        return;
    }
    offlineSyncEnsureSchema($mysqli);
    $script = basename($script);
    $stmt = $mysqli->prepare("
        INSERT IGNORE INTO offline_sync_log (client_id, user_id, script)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('sis', $client_id, $user_id, $script);
    $stmt->execute();
    $stmt->close();
}
