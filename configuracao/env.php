<?php
define('AUTH_API_LOGIN', 'https://frutag.com.br/sso/login.php');

define('JWT_ALGO', 'HS256');                         // <— HS256
define('JWT_SECRET', '}^BNS8~o80?RyV]d'); // IGUAL ao do Frutag
// (mantenha JWT_PUBLIC_KEY_PATH definido, mas ele será ignorado no HS256)
define('AUTH_COOKIE', 'token');
define('COOKIE_DOMAIN', '.frutag.com.br');

