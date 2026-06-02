const OfflineSync = (() => {
  const SALVAR_PATHS = [
    "salvar_adubacao_calcario.php",
    "salvar_adubacao_organica.php",
    "salvar_clima.php",
    "salvar_colheita.php",
    "salvar_colheita_hidroponia.php",
    "salvar_controle_agua.php",
    "salvar_coleta_analise.php",
    "salvar_defensivo_hidroponia.php",
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

  const CACHE_MAP = {
    "buscar_areas.php": "areas",
    "buscar_produtos.php": "produtos",
    "buscar_herbicidas.php": "herbicidas",
    "buscar_fertilizantes.php": "fertilizantes",
    "buscar_fungicidas.php": "fungicidas",
    "buscar_inseticidas.php": "inseticidas",
  };

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

  async function putDadosCache(data) {
    memDados = data;
    await OfflineDB.putCache("dados_offline", data);
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

  async function getDadosCache() {
    if (memDados) return memDados;
    memDados = await OfflineDB.getCache("dados_offline");
    return memDados;
  }

  async function getCachedList(cacheKey) {
    const dados = await getDadosCache();
    const list = dados?.[cacheKey];
    return Array.isArray(list) ? list : null;
  }

  async function mergeDadosSlice(cacheKey, arr) {
    if (!cacheKey || !Array.isArray(arr)) return;
    let dados = await getDadosCache();
    if (!dados?.ok) {
      dados = { ok: true, atualizado_em: new Date().toISOString() };
    }
    dados[cacheKey] = arr;
    await putDadosCache(dados);
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

  async function refillCatalogSelects() {
    const dados = await getDadosCache();
    if (!dados) return false;

    const areas = Array.isArray(dados.areas) ? dados.areas : [];
    const produtos = Array.isArray(dados.produtos) ? dados.produtos : [];

    document.querySelectorAll(".area-select").forEach((sel) => {
      const valorAtual = sel.value;
      sel.innerHTML = '<option value="">Selecione a área</option>';
      areas.forEach((item) => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = areaOptionLabel(item);
        if (String(item.id) === String(valorAtual)) opt.selected = true;
        sel.appendChild(opt);
      });
    });

    document.querySelectorAll(".produto-select").forEach((sel) => {
      const valorAtual = sel.value;
      sel.innerHTML = '<option value="">Selecione o produto</option>';
      produtos.forEach((item) => {
        const opt = document.createElement("option");
        opt.value = item.id;
        opt.textContent = item.nome;
        if (String(item.id) === String(valorAtual)) opt.selected = true;
        sel.appendChild(opt);
      });
    });

    return areas.length > 0 || produtos.length > 0;
  }

  async function warmCatalogFromNetwork(fetchFn = fetch) {
    await Promise.all(
      getCatalogFetchUrls().map((url) =>
        fetchFn(url, { credentials: "same-origin" }).catch(() => null)
      )
    );
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

  async function enqueue(url, formData) {
    const absUrl = resolveSalvarUrl(url) || String(url);
    const body = await formDataToObject(formData);
    await OfflineDB.addFila({
      id: uuid(),
      url: absUrl,
      body,
      criadoEm: Date.now(),
      tentativas: 0,
    });
  }

  async function syncAll(onProgress) {
    const items = await OfflineDB.listFila();
    let ok = 0;
    let fail = 0;

    for (const item of items) {
      try {
        const fd = objectToFormData(item.body);
        const postUrl = resolveSalvarUrl(item.url) || item.url;
        const r = await fetch(postUrl, { method: "POST", body: fd, credentials: "same-origin" });
        const res = await r.json();
        if (res.ok) {
          await OfflineDB.removeFila(item.id);
          ok++;
        } else {
          fail++;
        }
      } catch {
        fail++;
      }
      if (typeof onProgress === "function") onProgress({ ok, fail, total: items.length });
    }
    return { ok, fail, total: items.length };
  }

  return {
    SALVAR_PATHS,
    CACHE_MAP,
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
    getCachedList,
    mergeDadosSlice,
    warmDadosCache,
    hasCatalogData,
    getCatalogFetchUrls,
    warmCatalogFromNetwork,
    summarizeDados,
    putDadosCache,
    enqueue,
    syncAll,
  };
})();

window.OfflineSync = OfflineSync;
