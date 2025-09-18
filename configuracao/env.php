<?php
// Endpoint de login do Frutag (você vai criar no Frutag)
define('AUTH_API_LOGIN', 'https://frutag.com.br/api/auth/login');

// Caminho da CHAVE PÚBLICA (RS256) do Frutag para validação do token
define('JWT_PUBLIC_KEY_PATH', __DIR__ . '/jwt_public.pem');

// Nome do cookie com o JWT
define('AUTH_COOKIE', 'token');

// Domínio compartilhado para SSO (deve estar em HTTPS nos dois)
define('COOKIE_DOMAIN', '.frutag.com.br');
