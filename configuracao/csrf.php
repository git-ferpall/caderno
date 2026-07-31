<?php
declare(strict_types=1);

require_once __DIR__ . '/usuarios_local.php';

const CSRF_COOKIE = 'csrf_token';
const CSRF_HEADER = 'HTTP_X_CSRF_TOKEN';

/**
 * Caminhos isentos de CSRF (webhooks, login, cron).
 */
function csrf_is_exempt_request(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $exempt = [
        '/configuracao/login_process.php',
        '/funcoes/whatsapp/webhook.php',
        '/login/processa.php',
    ];
    foreach ($exempt as $prefix) {
        if (str_ends_with($path, $prefix) || $path === $prefix) {
            return true;
        }
    }
    if (php_sapi_name() === 'cli') {
        return true;
    }
    return false;
}

function csrf_cookie_options(int $ttl = 3600): array
{
    $opts = usuarioCookieOptions($ttl);
    $opts['httponly'] = false;
    return $opts;
}

function csrf_issue_token(int $ttl = 3600): string
{
    $token = bin2hex(random_bytes(32));
    setcookie(CSRF_COOKIE, $token, csrf_cookie_options($ttl));
    return $token;
}

/** Garante cookie CSRF em páginas autenticadas (double-submit). */
function csrf_ensure_cookie(): void
{
    if (!empty($_COOKIE[CSRF_COOKIE]) && is_string($_COOKIE[CSRF_COOKIE]) && strlen($_COOKIE[CSRF_COOKIE]) >= 32) {
        return;
    }
    csrf_issue_token();
}

/**
 * Valida CSRF em requisições mutáveis (POST/PUT/PATCH/DELETE).
 * Compara header X-CSRF-Token com cookie csrf_token.
 */
function csrf_verify(): void
{
    if (csrf_is_exempt_request()) {
        return;
    }

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $cookie = $_COOKIE[CSRF_COOKIE] ?? '';
    $header = $_SERVER[CSRF_HEADER] ?? '';
    $body = $_POST['csrf_token'] ?? '';

    $token = $header !== '' ? $header : $body;

    if ($cookie === '' || $token === '' || !hash_equals($cookie, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'err' => 'csrf_invalid'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
