<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$user_id = offlineAuthUserId();
if (!$user_id) {
    offlineJson(['ok' => false, 'msg' => 'Não autenticado.'], 401);
}

offlineJson([
    'ok' => true,
    'cache_static' => 'caderno-static-v20',
    'cache_pages' => 'caderno-pages-v20',
    'catalog_max_age_hours' => offlineCatalogMaxAgeHours(),
    'background_sync' => true,
    'salvar' => offlineSalvarEndpoints(),
    'catalog' => offlineCatalogMap(),
    'tipos' => offlineTipoLabels(),
    'shell_pages' => offlineShellPages(),
]);
