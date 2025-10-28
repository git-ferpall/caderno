document.getElementById("btn-silo-arquivo").addEventListener("click", () => {
  document.getElementById("inputUploadSilo").click();
});

document.getElementById("inputUploadSilo").addEventListener("change", function() {
  if (this.files.length > 0) {
    enviarArquivosSilo(this.files, window.pastaAtual || '');
  }
});