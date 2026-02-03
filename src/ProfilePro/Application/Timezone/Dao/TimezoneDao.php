<?php

namespace App\ProfilePro\Application\Timezone\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class TimezoneDao
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function updateForPro(
        int $proId,
        string $fullAddress,
        float $lat,
        float $lng,
        string $timezone
    ): void {
        $this->connection->executeStatement(
            'UPDATE pro
             SET location_full_text = :full_address,
                 latitude = :lat,
                 longitude = :lng,
                 timezone_iana = :timezone
             WHERE id = :pro_id AND deleted_at IS NULL',
            [
                'pro_id' => $proId,
                'full_address' => $fullAddress,
                'lat' => $lat,
                'lng' => $lng,
                'timezone' => $timezone,
            ],
            [
                'pro_id' => Types::INTEGER,
                'full_address' => Types::STRING,
                'lat' => Types::FLOAT,
                'lng' => Types::FLOAT,
                'timezone' => Types::STRING,
            ]
        );
    }
}
