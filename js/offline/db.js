const OfflineDB = (() => {
  const DB_NAME = "caderno_offline";
  const DB_VERSION = 1;

  function openDb() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onerror = () => reject(req.error);
      req.onupgradeneeded = () => {
        const db = req.result;
        if (!db.objectStoreNames.contains("fila")) {
          db.createObjectStore("fila", { keyPath: "id" });
        }
        if (!db.objectStoreNames.contains("cache")) {
          db.createObjectStore("cache", { keyPath: "key" });
        }
      };
      req.onsuccess = () => resolve(req.result);
    });
  }

  async function tx(storeName, mode, fn) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const t = db.transaction(storeName, mode);
      const store = t.objectStore(storeName);
      const result = fn(store);
      t.oncomplete = () => resolve(result);
      t.onerror = () => reject(t.error);
    });
  }

  async function putCache(key, value) {
    if (value == null) {
      await tx("cache", "readwrite", (s) => s.delete(key));
      return;
    }
    await tx("cache", "readwrite", (s) => s.put({ key, value, savedAt: Date.now() }));
  }

  async function getCache(key) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const t = db.transaction("cache", "readonly");
      const req = t.objectStore("cache").get(key);
      req.onsuccess = () => resolve(req.result?.value ?? null);
      req.onerror = () => reject(req.error);
    });
  }

  async function addFila(item) {
    await tx("fila", "readwrite", (s) => s.put(item));
  }

  async function listFila() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const t = db.transaction("fila", "readonly");
      const req = t.objectStore("fila").getAll();
      req.onsuccess = () => {
        const items = (req.result || []).sort((a, b) => a.criadoEm - b.criadoEm);
        resolve(items);
      };
      req.onerror = () => reject(req.error);
    });
  }

  async function removeFila(id) {
    await tx("fila", "readwrite", (s) => s.delete(id));
  }

  async function countFila() {
    const items = await listFila();
    return items.length;
  }

  return { putCache, getCache, addFila, listFila, removeFila, countFila };
})();

window.OfflineDB = OfflineDB;
