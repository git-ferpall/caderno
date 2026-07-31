<?php
declare(strict_types=1);

const LOGIN_RATE_LIMIT_MAX_FAILURES = 5;
const LOGIN_RATE_LIMIT_LOCK_SECONDS = 900;
const LOGIN_RATE_LIMIT_MAX_FAILURES_SCOPE = 8;
const LOGIN_RATE_LIMIT_SCOPE_LOCK_SECONDS = 1800;
const LOGIN_RATE_LIMIT_DELAY_MS_PER_FAILURE = 300;
const LOGIN_RATE_LIMIT_DELAY_MS_MAX = 3000;

function login_rate_limit_client_ip(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return trim(explode(',', (string) $ip)[0]);
}

/**
 * @return array<int, array{key: string, type: 'ip'|'scope'}>
 */
function login_rate_limit_entries(?string $scope = null): array
{
    $entries = [
        ['key' => hash('sha256', 'ip:' . login_rate_limit_client_ip()), 'type' => 'ip'],
    ];

    if ($scope !== null && $scope !== '') {
        $entries[] = [
            'key' => hash('sha256', 'scope:' . mb_strtolower(trim($scope))),
            'type' => 'scope',
        ];
    }

    return $entries;
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

function login_rate_limit_limits_for_type(string $type): array
{
    if ($type === 'scope') {
        return [
            'max_failures' => LOGIN_RATE_LIMIT_MAX_FAILURES_SCOPE,
            'lock_seconds' => LOGIN_RATE_LIMIT_SCOPE_LOCK_SECONDS,
        ];
    }

    return [
        'max_failures' => LOGIN_RATE_LIMIT_MAX_FAILURES,
        'lock_seconds' => LOGIN_RATE_LIMIT_LOCK_SECONDS,
    ];
}

function login_rate_limit_is_blocked(?string $scope = null): bool
{
    foreach (login_rate_limit_entries($scope) as $entry) {
        $data = login_rate_limit_read($entry['key']);
        $lockedUntil = (int) ($data['locked_until'] ?? 0);

        if ($lockedUntil > time()) {
            return true;
        }

        if ($lockedUntil > 0) {
            @unlink(login_rate_limit_file($entry['key']));
        }
    }

    return false;
}

function login_rate_limit_remaining_seconds(?string $scope = null): int
{
    $remaining = 0;

    foreach (login_rate_limit_entries($scope) as $entry) {
        $data = login_rate_limit_read($entry['key']);
        $remaining = max($remaining, max(0, (int) ($data['locked_until'] ?? 0) - time()));
    }

    return $remaining;
}

function login_rate_limit_failure_count(?string $scope = null): int
{
    $failures = 0;

    foreach (login_rate_limit_entries($scope) as $entry) {
        $data = login_rate_limit_read($entry['key']);
        $failures = max($failures, (int) ($data['failures'] ?? 0));
    }

    return $failures;
}

function login_rate_limit_apply_delay(?string $scope = null): void
{
    $failures = login_rate_limit_failure_count($scope);
    $delayUs = min(
        LOGIN_RATE_LIMIT_DELAY_MS_MAX * 1000,
        $failures * LOGIN_RATE_LIMIT_DELAY_MS_PER_FAILURE * 1000
    );

    if ($delayUs > 0) {
        usleep($delayUs);
    }
}

function login_rate_limit_record_failure(?string $scope = null): void
{
    foreach (login_rate_limit_entries($scope) as $entry) {
        $limits = login_rate_limit_limits_for_type($entry['type']);
        $data = login_rate_limit_read($entry['key']);
        $data['failures'] = (int) ($data['failures'] ?? 0) + 1;

        if ($data['failures'] >= $limits['max_failures']) {
            $data['locked_until'] = time() + $limits['lock_seconds'];
            $data['failures'] = 0;
        }

        login_rate_limit_write($entry['key'], $data);
    }

    login_rate_limit_apply_delay($scope);
}

function login_rate_limit_reset(?string $scope = null): void
{
    foreach (login_rate_limit_entries($scope) as $entry) {
        @unlink(login_rate_limit_file($entry['key']));
    }
}

function login_rate_limit_block_message(?string $scope = null): string
{
    $mins = max(1, (int) ceil(login_rate_limit_remaining_seconds($scope) / 60));
    return "Muitas tentativas de login. Aguarde {$mins} minuto(s) e tente novamente.";
}

function login_rate_limit_record_action(
    string $scope,
    int $maxAttempts = 10,
    int $lockSeconds = 900
): void {
    $key = hash('sha256', 'action:' . mb_strtolower(trim($scope)));
    $data = login_rate_limit_read($key);
    $data['failures'] = (int) ($data['failures'] ?? 0) + 1;

    if ($data['failures'] >= $maxAttempts) {
        $data['locked_until'] = time() + $lockSeconds;
        $data['failures'] = 0;
    }

    login_rate_limit_write($key, $data);
}

function login_rate_limit_is_action_blocked(
    string $scope,
    int $maxAttempts = 10,
    int $lockSeconds = 900
): bool {
    $key = hash('sha256', 'action:' . mb_strtolower(trim($scope)));
    $data = login_rate_limit_read($key);
    $lockedUntil = (int) ($data['locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        return true;
    }

    if ($lockedUntil > 0) {
        @unlink(login_rate_limit_file($key));
    }

    return (int) ($data['failures'] ?? 0) >= $maxAttempts;
}

function login_rate_limit_action_message(): string
{
    return 'Muitas ações em sequência. Aguarde alguns minutos e tente novamente.';
}
