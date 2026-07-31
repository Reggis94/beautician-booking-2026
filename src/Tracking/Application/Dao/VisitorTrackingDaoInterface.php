<?php

namespace App\Tracking\Application\Dao;

use App\Tracking\Application\Enum\IdentifierBlockReason;

interface VisitorTrackingDaoInterface
{
    public function isBlocked(string $ip, ?string $visitorId): bool;

    /**
     * @param list<array{
     *     limit: int,
     *     window_seconds: int,
     *     reason: IdentifierBlockReason
     * }> $tiers
     *
     * @return null|array{
     *     ip: ?string,
     *     visitor_id_cookie: ?string,
     *     window_seconds: int,
     *     reason: IdentifierBlockReason
     * }
     */
    public function findExceededRateLimit(string $ip, ?string $visitorId, array $tiers): ?array;

    public function blockIdentifiers(
        ?string $ip,
        ?string $visitorId,
        int $durationSeconds,
        IdentifierBlockReason $reason,
        ?int $suspiciousWindowSeconds = null
    ): void;

    public function createPageVisit(
        string $visitorId,
        string $ip,
        string $currentUrl,
        ?string $referer,
        ?string $userAgent
    ): void;

    public function visitorIdExists(string $visitorId): bool;
}
