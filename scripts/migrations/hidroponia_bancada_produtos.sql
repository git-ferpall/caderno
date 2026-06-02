-- Hidroponia: vários produtos (culturas) por bancada
-- Execute uma vez no banco de produção.

CREATE TABLE IF NOT EXISTS bancada_produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bancada_id INT NOT NULL,
  produto_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_bancada_produto (bancada_id, produto_id),
  KEY idx_bancada_produtos_bancada (bancada_id),
  KEY idx_bancada_produtos_produto (produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Copia vínculo existente (1 produto por bancada) para a nova tabela
INSERT IGNORE INTO bancada_produtos (bancada_id, produto_id)
SELECT id, produto_id
FROM bancadas
WHERE produto_id IS NOT NULL AND produto_id > 0;
