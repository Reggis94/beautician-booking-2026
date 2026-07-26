<?php

namespace App\Tracking\Application\Dao;

interface VisitorTrackingDaoInterface
{
    public function createPageVisit(
        string $visitorId,
        string $ip,
        string $currentUrl,
        ?string $referer,
        ?string $userAgent
    ): void;

    public function visitorIdExists(string $visitorId): bool;
}
