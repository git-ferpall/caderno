-- Fase 2 — IA Fitossanitária: validação do agrônomo e painel por área
-- Executar no banco caderno (docker: caderno-db)

CREATE TABLE IF NOT EXISTS fitossanitaria_validacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    propriedade_id INT UNSIGNED NOT NULL,
    area_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fs_val_prop_area (propriedade_id, area_id),
    KEY idx_fs_val_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
