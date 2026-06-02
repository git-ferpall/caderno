/**
 * Metadados do catálogo offline (validade e propriedade ativa).
 */
const OfflineCatalogMeta = (() => {
  const DEFAULT_MAX_AGE_H = 72;

  function maxAgeMs(hours) {
    const h = hours || DEFAULT_MAX_AGE_H;
    return h * 60 * 60 * 1000;
  }

  async function getPrepared() {
    return OfflineDB.getCache("offline_prepared");
  }

  async function savePreparedMeta(dados) {
    const prep = {
      at: Date.now(),
      dadosAt: dados?.atualizado_em || null,
      propriedadeId: dados?.propriedade?.id ?? null,
      propriedadeNome: dados?.propriedade?.nome ?? null,
    };
    await OfflineDB.putCache("offline_prepared", prep);
    return prep;
  }

  function formatAge(ms) {
    if (ms < 3600000) return `${Math.max(1, Math.round(ms / 60000))} min`;
    if (ms < 86400000) return `${Math.round(ms / 3600000)} h`;
    return `${Math.round(ms / 86400000)} dia(s)`;
  }

  async function getMaxAgeHours() {
    const m = await OfflineDB.getCache("offline_manifest");
    if (m?.catalog_max_age_hours) return Number(m.catalog_max_age_hours);
    return DEFAULT_MAX_AGE_H;
  }

  async function checkStale(hours) {
    const prep = await getPrepared();
    if (!prep?.at) return null;
    const age = Date.now() - prep.at;
    const max = maxAgeMs(hours ?? (await getMaxAgeHours()));
    if (age <= max) return null;
    return {
      ageLabel: formatAge(age),
      dadosAt: prep.dadosAt,
      propriedadeNome: prep.propriedadeNome,
    };
  }

  async function checkPropriedadeMismatch() {
    const prep = await getPrepared();
    if (!prep?.propriedadeId) return null;
    const dados =
      typeof OfflineSync !== "undefined" ? await OfflineSync.getDadosCache() : null;
    const currentId = dados?.propriedade?.id;
    if (!currentId) return null;
    if (String(prep.propriedadeId) === String(currentId)) return null;
    return {
      cached: prep.propriedadeNome || `ID ${prep.propriedadeId}`,
      current: dados.propriedade?.nome || `ID ${currentId}`,
    };
  }

  async function warnIfNeeded() {
    if (typeof OfflineUI === "undefined") return;
    const mismatch = await checkPropriedadeMismatch();
    if (mismatch) {
      OfflineUI.setBanner(
        `A propriedade ativa mudou (${mismatch.cached} → ${mismatch.current}). Use «Baixar para offline» de novo.`,
        "warn"
      );
      return;
    }
    const stale = await checkStale();
    if (stale) {
      OfflineUI.setBanner(
        `Catálogo baixado há ${stale.ageLabel}. Atualize com «Baixar para offline» para dados recentes.`,
        "warn"
      );
    }
  }

  return {
    savePreparedMeta,
    getPrepared,
    checkStale,
    checkPropriedadeMismatch,
    warnIfNeeded,
    DEFAULT_MAX_AGE_H,
  };
})();

window.OfflineCatalogMeta = OfflineCatalogMeta;
