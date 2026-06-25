<?php

namespace App\ProfilePro\Public\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class ProTimezoneQuery implements ProTimezoneQueryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getProTimezone(int $proId): string
    {
        return $this->connection->fetchOne(
            "SELECT COALESCE(timezone_iana, 'UTC')
             FROM pro
             WHERE id = :pro_id AND deleted_at IS NULL
             LIMIT 1",
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );
    }
}
