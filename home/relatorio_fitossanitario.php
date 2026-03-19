<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Relatório Fitossanitário - Frutag</title>

<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="/img/logo-icon.png">

<style>

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #ddd;
    border-top: 5px solid #4caf50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: auto;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

</style>
</head>

<body>
<div id="loading" style="display:none;"></div>
<?php include '../include/popups.php'; ?> 
<?php include '../include/menu.php'; ?>
<div id="loading" style="display:none;">
  <img id="loading-img" src="" style="display:none;">
</div>
<div id="pdf-loading" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="text-align:center">
        <div class="spinner"></div>
        <p style="margin-top:10px;font-weight:bold;color:#2e7d32">
            Gerando relatório, aguarde...
        </p>
    </div>
</div>

<main class="sistema">

    <div class="page-title">
        <h2 class="main-title cor-branco">Controle Fitossanitário</h2>
    </div>

    <div class="sistema-main container">

        <form id="rel-form" class="main-form">

            <!-- PROPRIEDADE -->
            <div class="form-campo">
                <label>Propriedade</label>
                <select id="pf-propriedades" class="form-select form-text" required>
                    <option value="">Carregando...</option>
                </select>
            </div>

            <!-- ÁREA -->
            <div class="form-campo">
                <label>Área (opcional)</label>
                <select id="pf-area" class="form-select form-text">
                    <option value="">Todas as áreas</option>
                </select>
            </div>

            <!-- PERÍODO -->
            <div class="form-campo">
                <label>Data Inicial</label>
                <input type="date" id="pf-ini" class="form-text" required>
            </div>

            <div class="form-campo">
                <label>Data Final</label>
                <input type="date" id="pf-fin" class="form-text" required>
            </div>

            <!-- BOTÃO -->
            <div class="form-submit">
                <button type="button" class="main-btn fundo-verde" id="form-pdf-relatorio">
                    <span class="main-btn-text">Gerar PDF</span>
                </button>
            </div>

        </form>

    </div>

</main>

<?php include '../include/imports.php'; ?>
<?php include '../include/footer.php'; ?>

<script src="../js/relatorio_fitossanitario.js"></script>




</body>
</html>