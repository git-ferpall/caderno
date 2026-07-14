<?php
// Carregador de segredos.
// Prioridade: variável de ambiente > configuracao/secrets.php (arquivo NÃO versionado).

if (!function_exists('caderno_secret')) {
    function caderno_secret(string $key, ?string $default = null): ?string
    {
        static $secrets = null;

        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }

        if ($secrets === null) {
            $path = __DIR__ . '/secrets.php';
            $secrets = is_file($path) ? (require $path) : [];
            if (!is_array($secrets)) $secrets = [];
        }

        return $secrets[$key] ?? $default;
    }
}
