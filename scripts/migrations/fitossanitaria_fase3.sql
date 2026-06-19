-- Fase 3 — IA Fitossanitária: AGROFIT, CSFI, lote Frutag, auditoria
-- Executar no banco caderno (docker: caderno-db)

CREATE TABLE IF NOT EXISTS csfi_culturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    nome_normalizado VARCHAR(255) NOT NULL,
    observacao TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_csfi_nome (nome_normalizado),
    KEY idx_csfi_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fitossanitaria_lotes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    propriedade_id INT UNSIGNED NOT NULL,
    area_id INT UNSIGNED NOT NULL,
    codigo_lote VARCHAR(48) NOT NULL,
    hash_auditoria CHAR(64) NOT NULL,
    score_nivel ENUM('VERDE', 'AMARELO', 'VERMELHO', 'CINZA') NULL,
    status_lote ENUM('liberado', 'atencao', 'bloqueado', 'indefinido') NOT NULL DEFAULT 'indefinido',
    payload_json MEDIUMTEXT NULL COMMENT 'Snapshot JSON do painel',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_lote_area (propriedade_id, area_id),
    UNIQUE KEY uk_codigo_lote (codigo_lote),
    KEY idx_lote_prop (propriedade_id),
    KEY idx_lote_hash (hash_auditoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Culturas CSFI / Minor Crop (amostra — expandir conforme base oficial)
INSERT IGNORE INTO csfi_culturas (nome, nome_normalizado, observacao) VALUES
('Morango', 'morango', 'Cultura típica CSFI — validação técnica recomendada'),
('Maracujá', 'maracuja', 'Minor crop — verificar registro MAPA por cultura'),
('Pimentão', 'pimentao', 'CSFI — exige receituário e validação'),
('Melão', 'melao', 'CSFI — atenção a LMR e carência'),
('Mirtilo', 'mirtilo', 'Minor crop'),
('Framboesa', 'framboesa', 'CSFI'),
('Amora', 'amora', 'CSFI'),
('Physalis', 'physalis', 'Minor crop'),
('Erva-mate', 'erva-mate', 'CSFI regional'),
('Carambola', 'carambola', 'Minor crop');
