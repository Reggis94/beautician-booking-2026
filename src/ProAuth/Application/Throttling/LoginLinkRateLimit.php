<?php

namespace App\ProAuth\Application\Throttling;

use App\ProAuth\Application\Dao\LoginTokenDaoInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Maximum number of handled login link requests of the same beautician over a sliding time window.
 */
final class LoginLinkRateLimit
{
    public function __construct(
        private readonly LoginTokenDaoInterface $loginTokenDao,
        #[Autowire('%pro_auth.login_link_rate_limit_max_requests%')]
        private readonly int $maxRequests,
        #[Autowire('%pro_auth.login_link_rate_limit_window_seconds%')]
        private readonly int $windowSeconds
    ) {
    }

    public function isReached(int $proId): bool
    {
        return $this->loginTokenDao->countCreatedWithinSeconds($proId, $this->windowSeconds) >= $this->maxRequests;
    }
}
