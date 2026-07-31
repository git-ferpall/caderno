-- Funcionários de conta (multi-usuário na mesma conta)
-- Executar uma vez no banco caderno.
-- Obs.: o schema também é migrado automaticamente por configuracao/usuarios_local.php
--       (padrão ensure-schema, igual aos demais módulos).
--
-- conta_pai   → id da conta principal (dono dos dados). NULL = conta normal.
-- papel_conta → papel do funcionário dentro da conta:
--                 'admin'     → gerencia todo o caderno da conta, inclusive funcionários
--                 'apontador' → só registra apontamentos
--
-- O funcionário é sempre um usuário LOCAL (login + senha próprios) e não acessa
-- a Frutag; no login, o JWT é emitido com sub = conta_pai (dono dos dados) e
-- claims extras func_id / func_nome / func_papel identificando o funcionário.

ALTER TABLE usuarios_caderno
    ADD COLUMN conta_pai INT UNSIGNED NULL AFTER criado_por,
    ADD COLUMN papel_conta ENUM('admin','apontador') NULL AFTER conta_pai,
    ADD KEY idx_usuarios_caderno_conta_pai (conta_pai);

-- Liberação da função por conta (aprovada pelo administrativo no Painel):
-- a conta só cria acessos de funcionários se tiver registro aqui, e o campo
-- 'limite' define o máximo de acessos ATIVOS simultâneos.
CREATE TABLE IF NOT EXISTS conta_funcionarios_config (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,   -- id da conta principal
    limite INT UNSIGNED NOT NULL DEFAULT 1,      -- máx. de funcionários ativos
    habilitado_por INT UNSIGNED NULL,            -- admin que liberou
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Propriedades permitidas por funcionário (vazio = acesso a todas)
CREATE TABLE IF NOT EXISTS conta_funcionario_propriedades (
    funcionario_id INT UNSIGNED NOT NULL,
    propriedade_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (funcionario_id, propriedade_id),
    KEY idx_cfp_propriedade (propriedade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contato de funcionários para alertas SMS/e-mail (migrado também em usuarios_local.php)
-- ALTER TABLE usuarios_caderno
--     ADD COLUMN telefone VARCHAR(20) NULL AFTER email,
--     ADD COLUMN aceita_email TINYINT(1) NOT NULL DEFAULT 0 AFTER telefone,
--     ADD COLUMN aceita_sms TINYINT(1) NOT NULL DEFAULT 0 AFTER aceita_email,
--     ADD COLUMN consentimento_contato_em DATETIME NULL AFTER aceita_sms;
