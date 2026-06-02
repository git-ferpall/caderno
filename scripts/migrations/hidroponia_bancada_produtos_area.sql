-- Área / percentual por produto na bancada (hidroponia)
-- Execute após hidroponia_bancada_produtos.sql
-- Idempotente: pode rodar mais de uma vez sem erro.

SET @db = DATABASE();

-- area_m2
SET @exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'bancada_produtos'
    AND COLUMN_NAME = 'area_m2'
);
SET @sql = IF(
  @exists = 0,
  'ALTER TABLE bancada_produtos ADD COLUMN area_m2 DECIMAL(10,2) NULL DEFAULT NULL AFTER produto_id',
  'SELECT ''Coluna area_m2 já existe — nada a fazer.'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- percentual
SET @exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'bancada_produtos'
    AND COLUMN_NAME = 'percentual'
);
SET @sql = IF(
  @exists = 0,
  'ALTER TABLE bancada_produtos ADD COLUMN percentual DECIMAL(5,2) NULL DEFAULT NULL AFTER area_m2',
  'SELECT ''Coluna percentual já existe — nada a fazer.'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
