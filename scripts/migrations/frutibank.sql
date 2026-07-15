-- Frutibank — cobranças via PIX (QR Code estático em layout de boleto)
-- Executar uma vez no banco caderno.
-- Obs.: o schema também é criado automaticamente por funcoes/frutibank/helpers.php
--       (padrão ensure-schema, igual ao módulo offline).

-- Usuários liberados para usar o Frutibank (liberação feita no painel admin)
CREATE TABLE IF NOT EXISTS frutibank_usuarios (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    habilitado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configuração do recebedor (chave PIX) de cada usuário
CREATE TABLE IF NOT EXISTS frutibank_config (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    chave_pix VARCHAR(140) NOT NULL,
    tipo_chave ENUM('cpf','cnpj','email','telefone','aleatoria') NOT NULL DEFAULT 'aleatoria',
    nome_recebedor VARCHAR(25) NOT NULL,   -- limite do BR Code (campo 59)
    cidade VARCHAR(15) NOT NULL,           -- limite do BR Code (campo 60)
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clientes de cobrança (pagadores) de cada usuário
CREATE TABLE IF NOT EXISTS frutibank_clientes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf_cnpj VARCHAR(14) NOT NULL,         -- somente dígitos (11 = CPF, 14 = CNPJ)
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_frutibank_clientes_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cobranças geradas
CREATE TABLE IF NOT EXISTS frutibank_cobrancas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    descricao VARCHAR(140) NULL,
    vencimento DATE NULL,
    txid VARCHAR(25) NOT NULL,             -- identificador no BR Code (campo 62-05)
    payload TEXT NOT NULL,                 -- PIX copia-e-cola congelado na criação
    status ENUM('pendente','pago','cancelada') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_frutibank_cobrancas_user (user_id),
    KEY idx_frutibank_cobrancas_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
