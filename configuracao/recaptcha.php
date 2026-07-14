<?php
// configuracao/recaptcha.php
require_once __DIR__ . '/secrets_loader.php';

define('RECAPTCHA_SITE_KEY', caderno_secret('RECAPTCHA_SITE_KEY', ''));
define('RECAPTCHA_SECRET_KEY', caderno_secret('RECAPTCHA_SECRET_KEY', ''));
?>
