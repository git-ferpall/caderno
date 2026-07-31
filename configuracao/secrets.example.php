<?php
// Copie este arquivo para configuracao/secrets.php e preencha com os valores reais.
// O secrets.php está no .gitignore e NUNCA deve ser commitado.
// Em produção, prefira definir estes valores como variáveis de ambiente.

return [
    // Segredo usado para assinar/verificar os JWT do SSO (igual ao servidor SSO).
    // Mínimo 32 caracteres (requisito firebase/php-jwt v7 para HS256).
    'JWT_SECRET' => 'defina-um-segredo-forte-com-pelo-menos-32-caracteres',

    // Senha do banco local (caderno-db).
    'DB_PASSWORD' => '',

    // AWS SNS (alertas SMS).
    'AWS_KEY' => '',
    'AWS_SECRET' => '',
    'AWS_REGION' => 'us-east-1',

    // Banco remoto Frutag (SSO).
    'FRUTAG_DB_HOST' => '',
    'FRUTAG_DB_USER' => '',
    'FRUTAG_DB_PASS' => '',
    'FRUTAG_DB_NAME' => '',

    // Google reCAPTCHA.
    'RECAPTCHA_SITE_KEY' => '',
    'RECAPTCHA_SECRET_KEY' => '',

    // SMTP (relatório semanal por e-mail).
    'SMTP_HOST' => 'mail.frutag.com.br',
    'SMTP_USER' => 'naoresponder@frutag.com.br',
    'SMTP_PASSWORD' => '',
    'SMTP_PORT' => '465',
];
