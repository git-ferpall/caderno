<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$user_id = offlineAuthUserId();
if (!$user_id) {
    offlineJson(['ok' => false], 401);
}

offlineJson(['ok' => true, 'pong' => true, 'ts' => time()]);
