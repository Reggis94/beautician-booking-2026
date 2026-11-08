<?php

namespace App\ProAuth\Application\Throttling;

/**
 * Whether a beautician may receive a new login link: neither the cooldown nor the rate limit refuses it.
 */
final class LoginLinkThrottle
{
    public function __construct(
        private readonly LoginLinkCooldown $loginLinkCooldown,
        private readonly LoginLinkRateLimit $loginLinkRateLimit
    ) {
    }

    public function isAllowed(int $proId): bool
    {
        return !$this->loginLinkCooldown->isActive($proId)
            && !$this->loginLinkRateLimit->isReached($proId);
    }
}
