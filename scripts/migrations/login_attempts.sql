CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    sucesso TINYINT(1) NOT NULL DEFAULT 0,
    motivo VARCHAR(50) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_login_time (login_hash, criado_em),
    KEY idx_login_attempts_ip_time (ip_hash, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
