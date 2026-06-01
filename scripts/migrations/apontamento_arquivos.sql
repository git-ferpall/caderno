CREATE TABLE IF NOT EXISTS apontamento_arquivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apontamento_id INT NOT NULL,
    silo_arquivo_id INT NOT NULL,
    user_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_apontamento_silo (apontamento_id, silo_arquivo_id),
    INDEX idx_apontamento_arquivos_apontamento (apontamento_id),
    INDEX idx_apontamento_arquivos_silo (silo_arquivo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
