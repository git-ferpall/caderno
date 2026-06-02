<?php
define('AUTH_API_LOGIN', 'https://frutag.com.br/sso/login.php');
define('JWT_ALGO', 'HS256');
define('JWT_SECRET', '}^BNS8~o80?RyV]d');  // igual ao SSO
define('AUTH_COOKIE', 'token');

// IDs Frutag (separados por vírgula) que podem gerenciar offline na primeira instalação.
define('OFFLINE_BOOTSTRAP_ADMINS', '2365');

// Dias que a sessão offline permanece válida após login online.
define('OFFLINE_SESSION_DAYS', 30);

