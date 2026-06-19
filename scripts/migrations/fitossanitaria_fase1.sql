-- Fase 1 — IA Fitossanitária: carência e data de liberação para colheita
-- Executar no banco caderno (docker: caderno-db)

-- Catálogo técnico (base para AGROFIT / cadastro manual)
CREATE TABLE IF NOT EXISTS produtos_fitossanitarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('herbicida', 'fungicida', 'inseticida', 'fertilizante') NOT NULL,
    nome VARCHAR(255) NOT NULL,
    ingrediente_ativo VARCHAR(255) NULL,
    carencia_dias INT UNSIGNED NULL COMMENT 'Intervalo de segurança em dias',
    registro_mapa VARCHAR(100) NULL,
    culturas TEXT NULL COMMENT 'Culturas autorizadas (texto ou JSON)',
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_fitossan_tipo_nome (tipo, nome),
    KEY idx_fitossan_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos técnicos nos catálogos já usados nos formulários (executar uma vez; ignorar erro se coluna já existir)
ALTER TABLE herbicidas ADD COLUMN carencia_dias INT UNSIGNED NULL;
ALTER TABLE herbicidas ADD COLUMN ingrediente_ativo VARCHAR(255) NULL;

ALTER TABLE fungicidas ADD COLUMN carencia_dias INT UNSIGNED NULL;
ALTER TABLE fungicidas ADD COLUMN ingrediente_ativo VARCHAR(255) NULL;

ALTER TABLE inseticidas ADD COLUMN carencia_dias INT UNSIGNED NULL;
ALTER TABLE inseticidas ADD COLUMN ingrediente_ativo VARCHAR(255) NULL;

-- Exemplo (ajuste conforme seu cadastro):
-- UPDATE herbicidas SET carencia_dias = 7, ingrediente_ativo = 'Glifosato' WHERE nome LIKE '%Roundup%' LIMIT 1;
