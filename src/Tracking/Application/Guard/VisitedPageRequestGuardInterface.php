<?php

namespace App\Tracking\Application\Guard;

interface VisitedPageRequestGuardInterface
{
    public function guard(
        string $ip,
        ?string $visitorCookieId,
        ?string $userAgent,
        callable $isBlocked,
        callable $findExceededRateLimit,
        callable $blockIdentifiers
    ): void;
}
