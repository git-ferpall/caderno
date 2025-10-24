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
</head>
<body>
    <?php include '../include/loading.php'; ?> 
    <?php include '../include/popups.php'; ?>

    <div id="conteudo">
        <?php include '../include/menu.php'; ?>

        <main id="hidroponia" class="sistema">
            <div class="page-title">
                <h2 class="main-title cor-branco">Hidroponia</h2>
            </div>

            <div class="sistema-main">
                <div class="item-box container">

                <?php
                // --- Carrega dados reais do banco ---
                $json = file_get_contents("../funcoes/carregar_hidroponia.php");
                $data = json_decode($json, true);
                $areas = ($data && $data['ok']) ? $data['areas'] : [];

                if (!empty($areas)) {
                    foreach ($areas as $area) {
                        echo '<h3 class="main-subtitle">'.$area['nome'].'</h3>';

                        if (!empty($area['estufas'])) {
                            foreach ($area['estufas'] as $estufa) {
                                $area_m2 = $estufa['area_m2'] ?? 'Não informado';
                                $obs = ($estufa['obs'] == '') ? 'Nenhuma observação' : $estufa['obs'];
                                $has_obs = ($estufa['obs'] == '') ? '' : 'v2';

                                echo '<div class="item item-propriedade item-estufa v2" id="estufa-' . $estufa['id'] . '">
                                    <h4 class="item-title">' . htmlspecialchars($estufa['nome']) . '</h4>
                                    <div class="item-edit">
                                        <button class="edit-btn" id="edit-estufa-' . $estufa['id'] . '" type="button" onclick="selectEstufa(' . $estufa['id'] . ')">
                                            Selecionar
                                        </button>
                                    </div>
                                </div>

                                <div class="item-estufa-box d-none" id="estufa-' . $estufa['id'] . '-box">
                                    <div class="item-estufa-header">
                                        <div class="item-estufa-header-box">
                                            <div class="item-estufa-title">Área (m²)</div>
                                            <div class="item-estufa-text">' . $area_m2 . '</div>
                                        </div>
                                        <div class="item-estufa-header-box ' . $has_obs . '">
                                            <div class="item-estufa-title">Observações</div>
                                            <div class="item-estufa-text">' . htmlspecialchars($obs) . '</div>
                                        </div>
                                    </div>
                                    <div class="item-bancadas">
                                        <h4 class="item-bancadas-title">Bancadas</h4>
                                        <div class="item-bancadas-box">';
                                
                                if (!empty($estufa['bancadas']) && is_array($estufa['bancadas'])) {
                                    foreach ($estufa['bancadas'] as $bancada) {
                                        $cultura = ($bancada['cultura'] == '') ? 'Não informado' : htmlspecialchars($bancada['cultura']);
                                        $bancada_obs = ($bancada['obs'] == '') ? 'Nenhuma observação' : htmlspecialchars($bancada['obs']);
                                        $bancada_has_obs = ($bancada['obs'] == '') ? '' : 'v2';
                                        $form_id = 'e-' . $estufa['id'] . '-b-' . $bancada['id'];

                                        echo '<button type="button" class="item-bancada" id="item-bancada-' . $bancada['id'] . '-estufa-' . $estufa['id'] . '" onclick="selectBancada(\'' . $bancada['id'] . '\', ' . $estufa['id'] . ')">
                                            <div class="item-bancada-title">' . htmlspecialchars($bancada['nome']) . '</div>
                                        </button>

                                        <div class="item-bancada-content d-none" id="item-bancada-' . $bancada['id'] . '-content-estufa-' . $estufa['id'] . '">
                                            <div class="item-bancada-header">
                                                <div class="item-bancada-header-box">
                                                    <div class="item-bancada-header-title">Cultura/espécie</div>
                                                    <div class="item-bancada-header-text">' . $cultura . '</div>
                                                </div>
                                                <div class="item-bancada-header-box ' . $bancada_has_obs . '">
                                                    <div class="item-bancada-header-title">Observações</div>
                                                    <div class="item-bancada-header-text">' . $bancada_obs . '</div>
                                                </div>
                                            </div>
                                            <div class="item-bancada-edit">
                                                <button class="item-btn item-bancada-btn fundo-cinza-b cor-preto" id="bancada-' . $bancada['id'] . '-cancel" type="button" onclick="voltarEstufa(' . $estufa['id'] . ')">
                                                    <span class="main-btn-text">Voltar</span>
                                                </button>
                                            </div>
                                        </div>';
                                    }
                                } else {
                                    echo '<div class="item-none">Nenhuma bancada cadastrada.</div>';
                                }

                                echo '</div>
                                    <form action="hidroponia.php" class="main-form form-bancada" id="add-bancada-estufa-' . $estufa['id'] . '">
                                        <div class="item-add">
                                            <button class="main-btn btn-alter btn-alter-item fundo-verde" id="bancada-add-estufa-' . $estufa['id'] . '" type="button">
                                                <div class="btn-icon icon-plus cor-verde"></div>
                                                <span class="main-btn-text">Nova Bancada</span>
                                            </button>
                                        </div>

                                        <div class="item-add-box" id="item-add-bancada-estufa-' . $estufa['id'] . '">
                                            <div class="item-add-box-p">
                                                <div class="form-campo">
                                                    <label class="item-label" for="b-nome">Número/Nome da Bancada</label>
                                                    <input type="text" class="form-text" name="enome" id="b-nome" placeholder="Ex: 01, 02..." required>
                                                </div>
                                                <div class="form-campo">
                                                    <label class="item-label" for="b-area">Cultura/Espécie</label>
                                                    <input type="text" class="form-text" name="barea" id="b-area" placeholder="Cultura ou espécie (Opcional)">
                                                </div>
                                                <div class="form-campo">
                                                    <label for="b-obs">Observações</label>
                                                    <textarea class="form-text form-textarea" name="bobs" id="b-obs" placeholder="Insira aqui suas observações..."></textarea>
                                                </div>
                                                <div class="form-submit">
                                                    <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-bancada-estufa-' . $estufa['id'] . '" type="button">
                                                        <span class="main-btn-text">Cancelar</span>
                                                    </button>
                                                    <button class="item-btn fundo-verde form-save" id="form-save-bancada-estufa-' . $estufa['id'] . '" type="button">
                                                        <span class="main-btn-text">Salvar</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>';
                            }
                        } else {
                            echo '<div class="item-none">Nenhuma estufa cadastrada nesta área.</div>';
                        }
                    }
                } else {
                    echo '<div class="item-none">Nenhuma área cadastrada.</div>';
                }
                ?>
                </div>

                <form action="hidroponia.php" class="main-form container" id="add-estufa">
                    <div class="item-add">
                        <button class="main-btn btn-alter btn-alter-item fundo-verde" id="estufa-add" type="button">
                            <div class="btn-icon icon-plus cor-verde"></div>
                            <span class="main-btn-text">Nova Estufa</span>
                        </button>
                    </div>
                    <div class="item-add-box" id="item-add-estufa">
                        <div class="item-add-box-p">
                            <div class="form-campo">
                                <label class="item-label" for="e-nome">Nome da Estufa</label>
                                <input type="text" class="form-text" name="enome" id="e-nome" placeholder="Ex: Estufa 01, Estufa 02..." required>
                            </div>
                            <div class="form-campo">
                                <label class="item-label" for="e-area">Área (m²)</label>
                                <input type="text" class="form-text" name="earea" id="e-area" placeholder="Área em m² (Opcional)">
                            </div>
                            <div class="form-campo">
                                <label for="e-obs">Observações</label>
                                <textarea class="form-text form-textarea" name="eobs" id="e-obs" placeholder="Insira aqui suas observações..."></textarea>
                            </div>
                            <div class="form-submit">
                                <button class="item-btn fundo-cinza-b cor-preto form-cancel" id="form-cancel-estufa" type="button">
                                    <span class="main-btn-text">Cancelar</span>
                                </button>
                                <button class="item-btn fundo-verde form-save" id="form-save-estufa" type="button">
                                    <span class="main-btn-text">Salvar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>

        <?php include '../include/imports.php'; ?>
    </div>
    <script src="../js/hidroponia.js"></script>
    <?php include '../include/footer.php'; ?>
</body>
</html>
