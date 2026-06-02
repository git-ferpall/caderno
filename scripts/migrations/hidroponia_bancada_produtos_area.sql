-- Área / percentual por produto na bancada (hidroponia)
-- Execute após hidroponia_bancada_produtos.sql

ALTER TABLE bancada_produtos
  ADD COLUMN area_m2 DECIMAL(10,2) NULL DEFAULT NULL AFTER produto_id,
  ADD COLUMN percentual DECIMAL(5,2) NULL DEFAULT NULL AFTER area_m2;
