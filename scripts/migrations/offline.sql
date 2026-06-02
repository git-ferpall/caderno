-- Offline parcial — controle Frutag + fila local no navegador
-- Executar uma vez no banco caderno

CREATE TABLE IF NOT EXISTS offline_admins (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    nome VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    adicionado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- offline_usuarios: só grava exceções. Sem registro = offline ATIVO (padrão).
-- habilitado = 0  →  cliente bloqueado manualmente pelo admin
CREATE TABLE IF NOT EXISTS offline_usuarios (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    habilitado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Primeiro admin (user_id do JWT / contato_cliente.user_id — NÃO é contato_cliente.id):
-- INSERT INTO offline_admins (user_id, nome, email) VALUES (2365, 'FABIANO AMARO', 'fbnamr@gmail.com');

-- Fase 2 (idempotência sync): executar também scripts/migrations/offline_fase2.sql
-- Fase 3 (background sync, catálogo): opcional offline_fase3.sql + OFFLINE_CATALOG_MAX_AGE_HOURS em env.php
