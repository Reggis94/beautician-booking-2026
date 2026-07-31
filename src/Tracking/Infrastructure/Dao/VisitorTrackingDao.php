<?php

namespace App\Tracking\Infrastructure\Dao;

use App\Tracking\Application\Dao\VisitorTrackingDaoInterface;
use App\Tracking\Application\Enum\IdentifierBlockReason;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class VisitorTrackingDao implements VisitorTrackingDaoInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function isBlocked(string $ip, ?string $visitorId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM blocked_access '
            . 'WHERE expires_at > NOW() AND (ip = :ip OR visitor_id_cookie = :visitor_id_cookie) LIMIT 1',
            ['ip' => $ip, 'visitor_id_cookie' => $visitorId],
            ['ip' => Types::STRING, 'visitor_id_cookie' => Types::STRING]
        );
    }

    public function findExceededRateLimit(string $ip, ?string $visitorId, array $tiers): ?array
    {
        $selects = [];
        $parameters = ['ip' => $ip, 'visitor_id' => $visitorId];
        $types = ['ip' => Types::STRING, 'visitor_id' => Types::STRING];

        foreach ($tiers as $index => $tier) {
            $windowParameter = 'window_' . $index;
            $selects[] = sprintf(
                "COUNT(*) FILTER (WHERE ip = :ip AND created_at >= NOW() - (:%s * INTERVAL '1 second')) AS ip_%d",
                $windowParameter,
                $index
            );
            $selects[] = sprintf(
                "COUNT(*) FILTER (WHERE visitor_id = :visitor_id AND created_at >= NOW() - (:%s * INTERVAL '1 second')) AS visitor_%d",
                $windowParameter,
                $index
            );
            $parameters[$windowParameter] = $tier['window_seconds'];
            $types[$windowParameter] = Types::INTEGER;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT ' . implode(', ', $selects) . ' FROM tracking_page_visit '
            . 'WHERE ip = :ip OR visitor_id = :visitor_id',
            $parameters,
            $types
        );
        foreach ($tiers as $index => $tier) {
            $ipExceeded = (int) ($row['ip_' . $index] ?? 0) >= $tier['limit'];
            $visitorIdExceeded = (int) ($row['visitor_' . $index] ?? 0) >= $tier['limit'];

            if (!$ipExceeded && !$visitorIdExceeded) {
                continue;
            }

            return [
                'ip' => $ipExceeded ? $ip : null,
                'visitor_id_cookie' => $visitorIdExceeded ? $visitorId : null,
                'window_seconds' => $tier['window_seconds'],
                'reason' => $tier['reason'],
            ];
        }

        return null;
    }

    public function blockIdentifiers(
        ?string $ip,
        ?string $visitorId,
        int $durationSeconds,
        IdentifierBlockReason $reason,
        ?int $suspiciousWindowSeconds = null
    ): void {
        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement(
                "INSERT INTO blocked_access (ip, visitor_id_cookie, expires_at, reason) "
                . "SELECT :ip, :visitor_id_cookie, NOW() + (:duration * INTERVAL '1 second'), :reason "
                . 'WHERE NOT EXISTS (SELECT 1 FROM blocked_access '
                . 'WHERE expires_at > NOW() AND ((:ip IS NOT NULL AND ip = :ip) '
                . 'OR (:visitor_id_cookie IS NOT NULL AND visitor_id_cookie = :visitor_id_cookie)))',
                [
                    'ip' => $ip,
                    'visitor_id_cookie' => $visitorId,
                    'duration' => $durationSeconds,
                    'reason' => $reason->value,
                ],
                [
                    'ip' => Types::STRING,
                    'visitor_id_cookie' => Types::STRING,
                    'duration' => Types::INTEGER,
                    'reason' => Types::STRING,
                ]
            );

            if ($suspiciousWindowSeconds === null) {
                $this->connection->commit();

                return;
            }

            $this->connection->executeStatement(
                "UPDATE tracking_page_visit SET is_suspicious = TRUE "
                . "WHERE created_at >= NOW() - (:window * INTERVAL '1 second') "
                . 'AND ((:ip IS NOT NULL AND ip = :ip) '
                . 'OR (:visitor_id IS NOT NULL AND visitor_id = :visitor_id))',
                ['window' => $suspiciousWindowSeconds, 'ip' => $ip, 'visitor_id' => $visitorId],
                ['window' => Types::INTEGER, 'ip' => Types::STRING, 'visitor_id' => Types::STRING]
            );
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function createPageVisit(
        string $visitorId,
        string $ip,
        string $currentUrl,
        ?string $referer,
        ?string $userAgent
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO tracking_page_visit (visitor_id, ip, current_url, referer, user_agent) '
            . 'VALUES (:visitor_id, :ip, :current_url, :referer, :user_agent)',
            [
                'visitor_id' => $visitorId,
                'ip' => $ip,
                'current_url' => $currentUrl,
                'referer' => $referer,
                'user_agent' => $userAgent,
            ],
            [
                'visitor_id' => Types::STRING,
                'ip' => Types::STRING,
                'current_url' => Types::TEXT,
                'referer' => Types::TEXT,
                'user_agent' => Types::TEXT,
            ]
        );
    }

    public function visitorIdExists(string $visitorId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM tracking_page_visit WHERE visitor_id = :visitor_id LIMIT 1',
            [
                'visitor_id' => $visitorId,
            ],
            [
                'visitor_id' => Types::STRING,
            ]
        );
    }
}
