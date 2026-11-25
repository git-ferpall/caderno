-- Soft-delete de apontamentos: some da visão operacional, permanece na timeline/relatórios.
ALTER TABLE apontamentos
    ADD COLUMN excluido_em DATETIME NULL DEFAULT NULL AFTER observacoes;

ALTER TABLE apontamentos
    ADD COLUMN motivo_exclusao TEXT NULL AFTER excluido_em;

CREATE INDEX idx_apontamentos_excluido_em ON apontamentos (excluido_em);
