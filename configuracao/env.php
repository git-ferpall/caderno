<?php
require_once __DIR__ . '/secrets_loader.php';

define('AUTH_API_LOGIN', 'https://frutag.com.br/sso/login.php');
define('JWT_ALGO', 'HS256');
define('JWT_SECRET', caderno_secret('JWT_SECRET', ''));  // igual ao SSO (env var ou secrets.php)
define('AUTH_COOKIE', 'token');

if (JWT_SECRET === '') {
    error_log('[caderno] JWT_SECRET não configurado (variável de ambiente ou configuracao/secrets.php).');
}

// IDs Frutag (separados por vírgula) que podem gerenciar offline na primeira instalação.
define('OFFLINE_BOOTSTRAP_ADMINS', '2365');

// IDs Frutag (separados por vírgula) promovidos a administradores do Caderno
// na primeira instalação do sistema de usuários (tabela usuarios_caderno).
define('CADERNO_BOOTSTRAP_ADMINS', '2365');

// Dias que a sessão offline permanece válida após login online.
define('OFFLINE_SESSION_DAYS', 30);

// Após quantas horas o catálogo local é considerado desatualizado (aviso ao usuário).
define('OFFLINE_CATALOG_MAX_AGE_HOURS', 72);

// Assistente por voz (OpenAI) — definir OPENAI_API_KEY no ambiente do servidor
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_WHISPER_MODEL', 'whisper-1');
define('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
define('OPENAI_API_BASE', 'https://api.openai.com/v1');

// WhatsApp Cloud API (Meta) — piloto Fase B
define('WHATSAPP_TOKEN', getenv('WHATSAPP_TOKEN') ?: '');
define('WHATSAPP_PHONE_NUMBER_ID', getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '');
define('WHATSAPP_VERIFY_TOKEN', getenv('WHATSAPP_VERIFY_TOKEN') ?: '');
define('WHATSAPP_APP_SECRET', getenv('WHATSAPP_APP_SECRET') ?: '');
define('WHATSAPP_API_VERSION', getenv('WHATSAPP_API_VERSION') ?: 'v21.0');

