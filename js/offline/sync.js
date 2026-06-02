const OfflineSync = (() => {
  const DEFAULT_SALVAR = [
    "salvar_adubacao_calcario.php",
    "salvar_adubacao_organica.php",
    "salvar_clima.php",
    "salvar_colheita.php",
    "salvar_colheita_hidroponia.php",
    "salvar_controle_agua.php",
    "salvar_coleta_analise.php",
    "salvar_defensivo_hidroponia.php",
    "salvar_estufa.php",
    "salvar_bancada.php",
    "salvar_erradicacao.php",
    "salvar_fertilizante.php",
    "salvar_fertilizante_hidroponia.php",
    "salvar_fungicida.php",
    "salvar_herbicida.php",
    "salvar_inseticida.php",
    "salvar_irrigacao.php",
    "salvar_manejo_integrado.php",
    "salvar_moscas_frutas.php",
    "salvar_personalizado.php",
    "salvar_plantio.php",
    "salvar_pragas_doencas.php",
    "salvar_revisao_maquinas.php",
    "salvar_transplantio.php",
    "salvar_visita_tecnica.php",
  ];

  const DEFAULT_CATALOG = {
    "buscar_areas.php": "areas",
    "buscar_produtos.php": "produtos",
    "buscar_herbicidas.php": "herbicidas",
    "buscar_fertilizantes.php": "fertilizantes",
    "buscar_fungicidas.php": "fungicidas",
    "buscar_inseticidas.php": "inseticidas",
  };

  let SALVAR_PATHS = [...DEFAULT_SALVAR];
  let CACHE_MAP = { ...DEFAULT_CATALOG };
  let TIPO_LABELS = {};

  function applyManifest(m) {
    if (m && Array.isArray(m.salvar) && m.salvar.length) SALVAR_PATHS = m.salvar;
    if (m && m.catalog && typeof m.catalog === "object") CACHE_MAP = m.catalog;
    if (m && m.tipos && typeof m.tipos === "object") TIPO_LABELS = m.tipos;
  }

  async function loadManifest(fetchFn) {
    const fn = fetchFn || window.__nativeFetch || fetch;
    try {
      const r = await fn(apiUrl("offline/manifest.php"), { credentials: "same-origin" });
      if (r.ok) {
        const m = await r.json();
        if (m.ok) {
          applyManifest(m);
          await OfflineDB.putCache("offline_manifest", m);
          return m;
        }
      }
    } catch (e) {
      console.warn("[offline] manifest:", e);
    }
    const cached = await OfflineDB.getCache("offline_manifest");
    if (cached) applyManifest(cached);
    return cached;
  }

  function getTipoLabels() {
    return { ...TIPO_LABELS };
  }

  function uuid() {
    return crypto.randomUUID?.() || `off-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  }

  function isSalvarUrl(url) {
    return !!resolveSalvarUrl(url);
  }

  function resolveSalvarUrl(url) {
    const u = String(url);
    for (const file of SALVAR_PATHS) {
      if (u.includes(file)) return apiUrl(file);
    }
    return null;
  }

  function isRelatorioUrl(url) {
    const u = String(url).toLowerCase();
    return u.includes("/funcoes/relatorios/") || u.includes("/relatorios") || u.includes("gerar_relatorio") || u.includes("pdf_");
  }

  function apiUrl(file) {
    const path = String(file).replace(/^\//, "");
    if (path.startsWith("funcoes/")) return `/${path}`;
    return `/funcoes/${path}`;
  }

  function getCacheKeyFromUrl(url) {
    const u = String(url);
    for (const [file, key] of Object.entries(CACHE_MAP)) {
      if (u.includes(file)) return key;
    }
    return null;
  }

  function getCatalogApiUrl(cacheKey) {
    for (const [file, key] of Object.entries(CACHE_MAP)) {
      if (key === cacheKey) return apiUrl(file);
    }
    return null;
  }

  async function formDataToObject(formData) {
    const obj = {};
    for (const [key, value] of formData.entries()) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        if (!Array.isArray(obj[key])) obj[key] = [obj[key]];
        obj[key].push(value);
      } else {
        obj[key] = value;
      }
    }
    return obj;
  }

  function objectToFormData(obj) {
    const fd = new FormData();
    Object.entries(obj).forEach(([key, val]) => {
      if (Array.isArray(val)) val.forEach((v) => fd.append(key, v));
      else if (val != null) fd.append(key, val);
    });
    return fd;
  }

  let memDados = null;

  const CATALOG_KEYS = [
    "areas",
    "produtos",
    "maquinas",
    "herbicidas",
    "fertilizantes",
    "fungicidas",
    "inseticidas",
  ];

  const DEFENSIVO_SELECTORS = [
    { selectId: "fungicida", cacheKey: "fungicidas", placeholder: "Selecione o fungicida" },
    { selectId: "herbicida", cacheKey: "herbicidas", placeholder: "Selecione o herbicida" },
    { selectId: "inseticida", cacheKey: "inseticidas", placeholder: "Selecione o inseticida" },
    { selectId: "fertilizante", cacheKey: "fertilizantes", placeholder: "Selecione o fertilizante" },
  ];

  async function putCatalogSlice(cacheKey, arr) {
    if (!cacheKey || !Array.isArray(arr)) return;
    await OfflineDB.putCache(`catalog_${cacheKey}`, arr);
  }

  async function getCatalogSlice(cacheKey) {
    const direct = await OfflineDB.getCache(`catalog_${cacheKey}`);
    if (Array.isArray(direct) && direct.length) return direct;
    return null;
  }

  async function putDadosCache(data) {
    memDados = data;
    await OfflineDB.putCache("dados_offline", data);
    if (data && typeof data === "object") {
      await Promise.all(
        CATALOG_KEYS.map(async (key) => {
          if (Array.isArray(data[key])) await putCatalogSlice(key, data[key]);
        })
      );
    }
    return data;
  }

  async function refreshDados(fetchFn) {
    const fn = fetchFn || window.__nativeFetch || fetch;
    const r = await fn(apiUrl("offline/dados.php"), { credentials: "same-origin" });
    if (!r.ok) throw new Error(`Falha ao baixar dados (${r.status})`);
    const data = await r.json();
    if (!data.ok) throw new Error(data.msg || "Falha ao cachear dados");
    return putDadosCache(data);
  }

  async function getDadosCache(forceReload = false) {
    if (!forceReload && memDados) return memDados;
    memDados = await OfflineDB.getCache("dados_offline");
    return memDados;
  }

  async function getCachedList(cacheKey) {
    const direct = await getCatalogSlice(cacheKey);
    if (direct) return direct;
    const dados = await getDadosCache();
    const list = dados?.[cacheKey];
    return Array.isArray(list) ? list : null;
  }

  async function mergeDadosSlice(cacheKey, arr) {
    if (!cacheKey || !Array.isArray(arr)) return;
    await putCatalogSlice(cacheKey, arr);
    let dados = (await getDadosCache()) || { ok: true, atualizado_em: new Date().toISOString() };
    dados[cacheKey] = arr;
    await putDadosCache(dados);
  }

  /** Baixa cada API buscar_* e grava no IndexedDB (redundância ao dados.php). */
  async function syncCatalogFromApis(fetchFn) {
    const fn = fetchFn || window.__nativeFetch || fetch;
    const errors = [];

    for (const [file, cacheKey] of Object.entries(CACHE_MAP)) {
      try {
        const r = await fn(apiUrl(file), { credentials: "same-origin" });
        if (!r.ok) {
          errors.push(`${cacheKey}:${r.status}`);
          continue;
        }
        const data = await r.json();
        if (!Array.isArray(data)) {
          errors.push(`${cacheKey}:formato`);
          continue;
        }
        await mergeDadosSlice(cacheKey, data);
      } catch {
        errors.push(`${cacheKey}:rede`);
      }
    }

    return errors;
  }

  async function warmDadosCache() {
    await getDadosCache();
  }

  function hasCatalogData(dados) {
    if (!dados) return false;
    return ["areas", "produtos"].some((k) => Array.isArray(dados[k]) && dados[k].length > 0);
  }

  function getCatalogFetchUrls() {
    return Object.keys(CACHE_MAP).map((file) => apiUrl(file));
  }

  function areaOptionLabel(item) {
    if (!item) return "";
    return item.tipo ? `${item.nome} (${item.tipo})` : String(item.nome ?? "");
  }

  const AREA_SELECTORS =
    ".area-select, .area-origem-select, .area-destino-select";
  const PRODUTO_SELECTORS =
    ".produto-select, select#produto, select[name='produto']:not([name*='[]'])";

  function fillSelectList(select, list, labelFn, placeholder) {
    const valorAtual = select.value;
    const firstOpt = select.querySelector("option");
    const place =
      firstOpt?.textContent?.trim() ||
      placeholder ||
      "Selecione";
    select.innerHTML = `<option value="">${place}</option>`;
    list.forEach((item) => {
      const opt = document.createElement("option");
      opt.value = item.id;
      opt.textContent = labelFn(item);
      if (String(item.id) === String(valorAtual)) opt.selected = true;
      select.appendChild(opt);
    });
  }

  async function refillDefensivoSelects() {
    for (const { selectId, cacheKey, placeholder } of DEFENSIVO_SELECTORS) {
      const sel = document.getElementById(selectId);
      if (!sel) continue;
      const list = (await getCachedList(cacheKey)) || [];
      if (!list.length) continue;
      fillSelectList(sel, list, (item) => item.nome, placeholder);
      if (typeof DefensivoOutro !== "undefined") {
        DefensivoOutro.afterCatalogLoaded(selectId);
      }
    }
  }

  async function refillMaquinaSelects() {
    const maquinas = (await getCachedList("maquinas")) || [];
    if (!maquinas.length) return;
    document.querySelectorAll("select#maquina, select[name='maquina']").forEach((sel) => {
      fillSelectList(
        sel,
        maquinas,
        (item) => (item.tipo ? `${item.nome} (${item.tipo})` : item.nome),
        "Selecione a máquina"
      );
    });
  }

  async function refillCatalogSelects() {
    await getDadosCache(true);
    const areas = (await getCachedList("areas")) || [];
    const produtos = (await getCachedList("produtos")) || [];
    const hasDefensivos = DEFENSIVO_SELECTORS.some((d) => document.getElementById(d.selectId));
    const hasMaquina = document.querySelector("select#maquina, select[name='maquina']");

    if (!areas.length && !produtos.length && !hasDefensivos && !hasMaquina) return false;

    if (areas.length) {
      document.querySelectorAll(AREA_SELECTORS).forEach((sel) => {
        const ph = sel.classList.contains("area-origem-select")
          ? "Selecione a área de origem"
          : sel.classList.contains("area-destino-select")
            ? "Selecione a área de destino"
            : "Selecione a área";
        fillSelectList(sel, areas, areaOptionLabel, ph);
      });
    }

    if (produtos.length) {
      document.querySelectorAll(PRODUTO_SELECTORS).forEach((sel) => {
        fillSelectList(sel, produtos, (item) => item.nome, "Selecione o produto");
      });
    }

    await refillDefensivoSelects();
    await refillMaquinaSelects();

    return areas.length > 0 || produtos.length > 0 || hasDefensivos || !!hasMaquina;
  }

  async function warmCatalogFromNetwork(fetchFn) {
    return syncCatalogFromApis(fetchFn || window.__nativeFetch || fetch);
  }

  async function getCatalogStatus() {
    const areas = (await getCachedList("areas")) || [];
    const produtos = (await getCachedList("produtos")) || [];
    const dados = await getDadosCache();
    return {
      areas: areas.length,
      produtos: produtos.length,
      propriedade: dados?.propriedade?.nome || null,
    };
  }

  function summarizeDados(dados) {
    if (!dados) return { areas: 0, produtos: 0, maquinas: 0 };
    const count = (k) => (Array.isArray(dados[k]) ? dados[k].length : 0);
    return {
      areas: count("areas"),
      produtos: count("produtos"),
      maquinas: count("maquinas"),
      herbicidas: count("herbicidas"),
      fertilizantes: count("fertilizantes"),
      fungicidas: count("fungicidas"),
      inseticidas: count("inseticidas"),
    };
  }

  function scriptFromSalvarUrl(url) {
    const m = String(url).match(/salvar_[a-z0-9_]+\.php/i);
    return m ? m[0] : null;
  }

  async function enqueue(url, formData) {
    const absUrl = resolveSalvarUrl(url) || String(url);
    const id = uuid();
    const body = await formDataToObject(formData);
    body.client_id = id;
    await OfflineDB.addFila({
      id,
      url: absUrl,
      body,
      criadoEm: Date.now(),
      tentativas: 0,
    });
  }

  async function syncOneItem(item, fetchFn) {
    const fn = fetchFn || window.__nativeFetch || fetch;
    const syncHeader =
      (typeof OfflineConstants !== "undefined" && OfflineConstants.SYNC_HEADER) || "X-Offline-Sync";
    const script = scriptFromSalvarUrl(item.url);
    if (!script) {
      return { ok: false, err: "URL de salvamento inválida" };
    }

    const fd = objectToFormData(item.body);
    fd.append("client_id", item.id);
    fd.append("_offline_script", script);

    const r = await fn(apiUrl("offline/forward.php"), {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { [syncHeader]: "1" },
    });
    const res = await r.json().catch(() => ({}));
    if (res.ok) {
      await OfflineDB.removeFila(item.id);
      return { ok: true, res };
    }
    item.tentativas = (item.tentativas || 0) + 1;
    item.lastError = res.err || res.msg || `HTTP ${r.status}`;
    await OfflineDB.addFila(item);
    return { ok: false, res, item };
  }

  async function syncAll(onProgress, fetchFn) {
    const items = await OfflineDB.listFila();
    let ok = 0;
    let fail = 0;

    for (const item of items) {
      try {
        const result = await syncOneItem(item, fetchFn);
        if (result.ok) ok++;
        else fail++;
      } catch (e) {
        item.tentativas = (item.tentativas || 0) + 1;
        item.lastError = e?.message || "rede";
        await OfflineDB.addFila(item);
        fail++;
      }
      if (typeof onProgress === "function") onProgress({ ok, fail, total: items.length });
    }
    return { ok, fail, total: items.length };
  }

  async function removeFromQueue(id) {
    await OfflineDB.removeFila(id);
  }

  return {
    SALVAR_PATHS: () => [...SALVAR_PATHS],
    CACHE_MAP: () => ({ ...CACHE_MAP }),
    loadManifest,
    applyManifest,
    getTipoLabels,
    DEFENSIVO_SELECTORS,
    DEFAULT_SALVAR,
    DEFAULT_CATALOG,
    isSalvarUrl,
    resolveSalvarUrl,
    isRelatorioUrl,
    getCacheKeyFromUrl,
    getCatalogApiUrl,
    formDataToObject,
    objectToFormData,
    apiUrl,
    refreshDados,
    getDadosCache,
    refillCatalogSelects,
    refillMaquinaSelects,
    refillDefensivoSelects,
    AREA_SELECTORS,
    PRODUTO_SELECTORS,
    fillSelectList,
    getCachedList,
    mergeDadosSlice,
    warmDadosCache,
    hasCatalogData,
    getCatalogFetchUrls,
    warmCatalogFromNetwork,
    syncCatalogFromApis,
    getCatalogStatus,
    summarizeDados,
    putDadosCache,
    enqueue,
    syncAll,
    syncOneItem,
    removeFromQueue,
    scriptFromSalvarUrl,
  };
})();

window.OfflineSync = OfflineSync;
