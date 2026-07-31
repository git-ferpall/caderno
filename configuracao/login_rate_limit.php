<?php
declare(strict_types=1);

const LOGIN_RATE_LIMIT_MAX_FAILURES = 5;
const LOGIN_RATE_LIMIT_LOCK_SECONDS = 900;

function login_rate_limit_key(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = trim(explode(',', (string) $ip)[0]);
    return hash('sha256', $ip);
}

function login_rate_limit_dir(): string
{
    $dir = rtrim(sys_get_temp_dir(), '/\\') . '/caderno_login_rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function login_rate_limit_file(string $key): string
{
    return login_rate_limit_dir() . '/' . $key . '.json';
}

function login_rate_limit_read(string $key): array
{
    $file = login_rate_limit_file($key);
    if (!is_file($file)) {
        return ['failures' => 0, 'locked_until' => 0];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : ['failures' => 0, 'locked_until' => 0];
}

function login_rate_limit_write(string $key, array $data): void
{
    file_put_contents(login_rate_limit_file($key), json_encode($data), LOCK_EX);
}

function login_rate_limit_is_blocked(): bool
{
    $key = login_rate_limit_key();
    $data = login_rate_limit_read($key);
    $lockedUntil = (int) ($data['locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        return true;
    }

    if ($lockedUntil > 0) {
        login_rate_limit_reset();
    }

    return false;
}

function login_rate_limit_remaining_seconds(): int
{
    $data = login_rate_limit_read(login_rate_limit_key());
    return max(0, (int) ($data['locked_until'] ?? 0) - time());
}

function login_rate_limit_record_failure(): void
{
    $key = login_rate_limit_key();
    $data = login_rate_limit_read($key);
    $data['failures'] = (int) ($data['failures'] ?? 0) + 1;

    if ($data['failures'] >= LOGIN_RATE_LIMIT_MAX_FAILURES) {
        $data['locked_until'] = time() + LOGIN_RATE_LIMIT_LOCK_SECONDS;
        $data['failures'] = 0;
    }

    login_rate_limit_write($key, $data);
}

function login_rate_limit_reset(): void
{
    @unlink(login_rate_limit_file(login_rate_limit_key()));
}
