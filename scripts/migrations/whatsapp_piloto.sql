-- Piloto WhatsApp — Caderno Frutag (Fase B)
-- Executar uma vez no banco caderno

CREATE TABLE IF NOT EXISTS whatsapp_vinculos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    telefone_e164 VARCHAR(20) NOT NULL,
    wa_id VARCHAR(20) NOT NULL COMMENT 'ID WhatsApp sem + (ex: 5511999999999)',
    opt_in_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wa_id (wa_id),
    UNIQUE KEY uq_telefone_e164 (telefone_e164),
    KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS whatsapp_sessoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    wa_id VARCHAR(20) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    intent_json JSON NOT NULL,
    resolucao_json JSON NOT NULL,
    resumo TEXT NOT NULL,
    expira_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wa_id (wa_id),
    KEY idx_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
