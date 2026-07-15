-- Usuários locais + perfis (admin / representante / usuario)
-- Executar uma vez no banco caderno.
-- Obs.: o schema também é criado automaticamente por configuracao/usuarios_local.php
--       (padrão ensure-schema, igual ao módulo offline).

-- Tabela única de usuários do Caderno:
--   origem = 'frutag'  → id é o próprio ID Frutag (JWT.sub); auto-provisionado no login
--   origem = 'local'   → id gerado a partir de 1.000.000 (não colide com IDs Frutag)
CREATE TABLE IF NOT EXISTS usuarios_caderno (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    origem ENUM('frutag','local') NOT NULL DEFAULT 'local',
    tipo_frutag VARCHAR(20) NULL,                -- 'cliente' | 'usuario' (só origem frutag)
    login VARCHAR(100) NULL,                     -- só usuários locais
    email VARCHAR(255) NULL,
    senha_hash VARCHAR(255) NULL,                -- password_hash(); só usuários locais
    nome VARCHAR(255) NULL,
    perfil ENUM('usuario','representante','admin') NOT NULL DEFAULT 'usuario',
    criado_por INT UNSIGNED NULL,                -- id do admin/representante que cadastrou
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_caderno_login (login),
    KEY idx_usuarios_caderno_criado_por (criado_por),
    KEY idx_usuarios_caderno_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000000;

-- Primeiro admin (ID Frutag — mesmo valor de CADERNO_BOOTSTRAP_ADMINS em configuracao/env.php):
-- INSERT INTO usuarios_caderno (id, origem, tipo_frutag, nome, perfil)
-- VALUES (2365, 'frutag', 'cliente', 'FABIANO AMARO', 'admin')
-- ON DUPLICATE KEY UPDATE perfil = 'admin';
