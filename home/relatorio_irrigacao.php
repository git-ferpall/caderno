<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatorio de Irrigacao - Frutag</title>
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
<?php include '../include/popups.php'; ?>
<?php include '../include/menu.php'; ?>

<div id="pdf-loading" style="display:none;position:fixed;inset:0;background:rgba(255,255,255,0.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="text-align:center">
        <div class="spinner"></div>
        <p style="margin-top:10px;font-weight:bold;color:#2e7d32">
            Gerando relatorio, aguarde...
        </p>
    </div>
</div>

<div id="conteudo">
    <main class="sistema">
        <div class="page-title">
            <h2 class="main-title cor-branco">Relatorio de Irrigacao</h2>
        </div>

        <div class="sistema-main container">
            <form id="rel-form" class="main-form">
                <div class="form-campo">
                    <label>Propriedade</label>
                    <select id="ri-propriedade" class="form-select form-text" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>

                <div class="form-campo">
                    <label>Areas (selecione uma ou mais)</label>
                    <div id="ri-areas" class="form-text" style="max-height:220px;overflow:auto;background:#fff;border:1px solid #d9d9d9;border-radius:8px;padding:10px;">
                        <span style="color:#777;">Selecione uma propriedade para carregar as areas.</span>
                    </div>
                </div>

                <div class="form-campo">
                    <label>Data Inicial</label>
                    <input type="date" id="ri-ini" class="form-text" required>
                </div>

                <div class="form-campo">
                    <label>Data Final</label>
                    <input type="date" id="ri-fin" class="form-text" required>
                </div>

                <div class="form-submit">
                    <button type="button" class="main-btn fundo-verde" id="ri-gerar-pdf">
                        <span class="main-btn-text">Gerar PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include '../include/imports.php'; ?>
<div id="footer">
    <?php include '../include/footer.php'; ?>
</div>

<script src="../js/relatorio_irrigacao.js"></script>
</body>
</html>
