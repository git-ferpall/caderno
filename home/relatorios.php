<?php
require_once __DIR__ . '/../configuracao/protect.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo - Frutag</title>

    <link rel="stylesheet" href="../css/style.css">

    <link rel="icon" type="image/png" href="/img/logo-icon.png">
<style>
    #pf-propriedades {
  height: auto;
  min-height: 48px;
  max-height: 140px; /* define altura visível */
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
  background-color: #f8f8f8;
  font-size: 15px;
  overflow-y: auto;
  cursor: pointer;
}

#pf-propriedades option {
  padding: 6px;
}

#pf-propriedades option:checked {
  background-color: #4caf50;
  color: #fff;
}

</style>    
</head>
<body>
    <?php include '../include/loading.php' ?> 
    <?php include '../include/popups.php' ?> 

    <div id="conteudo">
        <?php include '../include/menu.php' ?>

        <?php
        date_default_timezone_set("America/Sao_Paulo");

        // Aqui vai uma função pra pegar as informações do sistema que, caso possua algum dado cadastrado, esse valor já é colocado automaticamente no campo passível de edição

        $cultivos = [];
        $areas = [];
        $manejos = [];

        $dt_ini = date("Y-m-01");
        $dt_fin = date("Y-m-t");
        ?>

        <main id="relatorios" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Relatórios</h2>
            </div>

            <div class="sistema-main">
                <form action="relatorios.php" class="main-form container" id="rel-form">

                    <div class="form-campo">
                        <label for="pf-propriedades">Propriedades</label>
                        <select name="pfpropriedades[]" id="pf-propriedades" class="form-select form-text f1" multiple required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>

                    <div class="form-campo">
                        <label for="pf-cult">Cultivos</label>
                        <select name="pfcult" id="pf-cult" class="form-select form-text f1" required>
                            <option value="" selected>Todos os cultivos</option>
                            <?php
                            if (!empty($cultivos)) {
                                foreach ($cultivos as $cultivo) {
                                    echo '<option value="' . strtolower($cultivo) . '">' . $cultivo . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-campo">
                        <label for="pf-area">Áreas</label>
                        <select name="pfarea" id="pf-area" class="form-select form-text f1" required>
                            <option value="" selected>Todas as áreas</option>
                            <?php
                            if (!empty($areas)) {
                                foreach ($areas as $area) {
                                    echo '<option value="' . strtolower($area) . '">' . $area . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-campo">
                        <label for="pf-mane">Tipos de manejo</label>
                        <select name="pfmane" id="pf-mane" class="form-select form-text f1" required>
                            <option value="" selected>Todos os tipos de manejo</option>
                            <?php
                            if (!empty($manejos)) {
                                foreach ($manejos as $manejo) {
                                    echo '<option value="' . strtolower($manejo) . '">' . $manejo . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-campo">
                        <label for="pf-ini">Data Inicial</label>
                        <input class="form-text only-num" type="date" name="pfini" id="pf-ini" value="<?php echo $dt_ini ?>" required>
                    </div>

                    <div class="form-campo">
                        <label for="pf-fin">Data Final</label>
                        <input class="form-text only-num" type="date" name="pffin" id="pf-fin" value="<?php echo $dt_fin ?>" required>
                    </div>

                    <div class="form-submit">
                        <button class="main-btn fundo-laranja" id="form-pdf-relatorio" type="button">
                            <!-- <div class="btn-icon icon-check cor-verde"></div> -->
                            <span class="main-btn-text">Gerar PDF</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include '../include/imports.php' ?>
        <script src="../js/relatorios.js"></script>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
    $('#pf-propriedades').select2({
        placeholder: "Selecione uma ou mais propriedades",
        width: '100%',
        language: "pt-BR"
    });
    });
    </script>

        
    <?php include '../include/footer.php' ?>
</body>
</html>