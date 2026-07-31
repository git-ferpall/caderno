<?php
require_once __DIR__ . '/secrets_loader.php';

define('AWS_KEY', caderno_secret('AWS_KEY', ''));
define('AWS_SECRET', caderno_secret('AWS_SECRET', ''));
define('AWS_REGION', caderno_secret('AWS_REGION', 'us-east-1') ?: 'us-east-1');

