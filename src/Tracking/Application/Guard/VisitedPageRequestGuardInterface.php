<?php

namespace App\Tracking\Application\Guard;

interface VisitedPageRequestGuardInterface
{
    public function guard(
        string $ip,
        ?string $visitorId,
        ?string $userAgent,
        callable $isBlocked,
        callable $findExceededRateLimit,
        callable $blockIdentifiers
    ): void;
}
