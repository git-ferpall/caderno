<?php
// configuracao/recaptcha.php
require_once __DIR__ . '/secrets_loader.php';

// A site key é pública (aparece no HTML da página), então pode ficar no código.
// A secret key é confidencial: só via variável de ambiente ou secrets.php.
define('RECAPTCHA_SITE_KEY', caderno_secret('RECAPTCHA_SITE_KEY', '6LcB3OIrAAAAAKTdgcdNzRMV63Wd3QNQ47DCvFEH'));
define('RECAPTCHA_SECRET_KEY', caderno_secret('RECAPTCHA_SECRET_KEY', ''));
?>
