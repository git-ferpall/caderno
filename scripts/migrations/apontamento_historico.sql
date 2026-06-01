CREATE TABLE IF NOT EXISTS apontamento_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apontamento_id INT NOT NULL,
    user_id INT NOT NULL,
    campo VARCHAR(100) NOT NULL,
    valor_anterior TEXT NULL,
    valor_novo TEXT NULL,
    alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_apontamento_historico_apontamento (apontamento_id),
    INDEX idx_apontamento_historico_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
