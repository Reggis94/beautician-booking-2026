<?php

namespace App\Tracking\Infrastructure\Dao;

use App\Tracking\Application\Dao\VisitorTrackingDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class VisitorTrackingDao implements VisitorTrackingDaoInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
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
