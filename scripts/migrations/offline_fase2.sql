-- Fase 2 offline — idempotência de sincronização (evita duplicar apontamentos)
-- Executar uma vez no banco caderno

CREATE TABLE IF NOT EXISTS offline_sync_log (
    client_id VARCHAR(64) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    script VARCHAR(128) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id),
    KEY idx_offline_sync_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
