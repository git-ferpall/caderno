// js/hidroponia/hidroponia.js
import { carregarEstufas } from './estufa.js';
import { inicializarBancadas } from './bancada.js';

document.addEventListener("DOMContentLoaded", () => {
  console.log("🔹 Módulo Hidroponia inicializado");
  
  // Inicializa módulos
  carregarEstufas();
  inicializarBancadas();
});
