<?php

namespace App\ProAuth\Application\Throttling;

use App\ProAuth\Application\Dao\LoginTokenDaoInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Minimum delay between two handled login link requests of the same beautician.
 */
final class LoginLinkCooldown
{
    public function __construct(
        private readonly LoginTokenDaoInterface $loginTokenDao,
        #[Autowire('%pro_auth.login_link_cooldown_seconds%')]
        private readonly int $cooldownSeconds
    ) {
    }

    public function isActive(int $proId): bool
    {
        return $this->loginTokenDao->existsCreatedWithinSeconds($proId, $this->cooldownSeconds);
    }
}
