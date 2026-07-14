<?php
// Copie este arquivo para configuracao/secrets.php e preencha com os valores reais.
// O secrets.php está no .gitignore e NUNCA deve ser commitado.
// Em produção, prefira definir estes valores como variáveis de ambiente.

return [
    // Segredo usado para assinar/verificar os JWT do SSO (igual ao servidor SSO).
    'JWT_SECRET' => 'defina-um-segredo-forte-aqui',

    // Senha do banco local (caderno-db).
    'DB_PASSWORD' => '',

    // Banco remoto Frutag (SSO).
    'FRUTAG_DB_HOST' => '',
    'FRUTAG_DB_USER' => '',
    'FRUTAG_DB_PASS' => '',
    'FRUTAG_DB_NAME' => '',

    // Google reCAPTCHA.
    'RECAPTCHA_SITE_KEY' => '',
    'RECAPTCHA_SECRET_KEY' => '',
];
