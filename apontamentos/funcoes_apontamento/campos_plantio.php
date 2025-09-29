<?php
require_once __DIR__ . '/campos_comuns.php';

// Conjunto de campos específicos do apontamento "Plantio"
function campos_plantio($id) {
    campo_data($id);
    campo_area_cultivada($id);
    campo_produto_cultivado($id);
    campo_quantidade($id);
    campo_previsao_colheita($id);
    campo_obs($id);
}
