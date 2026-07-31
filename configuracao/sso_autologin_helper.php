<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

const SSO_AUTOLOGIN_MAX_AGE_SECONDS = 60;

/**
 * Gera URL de autologin SSO com expiração curta.
 * Formato da assinatura: HMAC-SHA256(uid|exp, JWT_SECRET)
 */
function ssoAutologinUrl(string $uid, ?int $ttlSeconds = null): string
{
    $ttl = $ttlSeconds ?? SSO_AUTOLOGIN_MAX_AGE_SECONDS;
    $exp = time() + max(1, min($ttl, SSO_AUTOLOGIN_MAX_AGE_SECONDS));
    $sig = hash_hmac('sha256', $uid . '|' . $exp, JWT_SECRET);

    return '/configuracao/sso_autologin.php?'
        . http_build_query([
            'uid' => $uid,
            'exp' => $exp,
            'sig' => $sig,
        ]);
}
