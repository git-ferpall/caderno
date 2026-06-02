-- Fase 3 offline — índices opcionais em offline_sync_log (fase 2)
-- Executar apenas se a tabela offline_sync_log já existir.
-- Em MySQL antigo, ignore erro "Duplicate key name" se o índice já existir.

-- CREATE INDEX idx_offline_sync_criado ON offline_sync_log (criado_em);
-- CREATE INDEX idx_offline_sync_script ON offline_sync_log (script);
