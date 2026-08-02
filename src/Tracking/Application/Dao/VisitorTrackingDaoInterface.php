<?php

namespace App\Tracking\Application\Dao;

use App\Tracking\Application\Enum\IdentifierBlockReason;

interface VisitorTrackingDaoInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    /** Locks tracking rate checks and writes until the current transaction ends. */
    public function lockPageVisitWrites(): void;

    public function isBlocked(string $ip, ?string $visitorCookieId): bool;

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
    public function findExceededRateLimit(string $ip, ?string $visitorCookieId, array $tiers): ?array;

    /** Persists a block within the transaction opened by the command handler. */
    public function blockIdentifiers(
        ?string $ip,
        ?string $visitorCookieId,
        int $durationSeconds,
        IdentifierBlockReason $reason,
        ?int $suspiciousWindowSeconds = null
    ): void;

    public function createPageVisit(
        string $visitorCookieId,
        string $ip,
        string $currentUrl,
        ?string $referer,
        ?string $userAgent
    ): void;

    public function visitorIdExists(string $visitorCookieId): bool;
}
