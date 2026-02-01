<?php

namespace App\Availability\Infrastructure\Repository;

use App\Availability\Domain\Entity\OffFullDayEntity;
use App\Availability\Domain\Repository\OffFullDayRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class OffFullDayRepository implements OffFullDayRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(OffFullDayEntity $offFullDay): int
    {
        $this->connection->executeStatement(
            'INSERT INTO off_full_day (pro_id, date, created_at)
             VALUES (:pro_id, :date, NOW())',
            [
                'pro_id' => $offFullDay->getProId(),
                'date' => $offFullDay->getDate(),
            ],
            [
                'pro_id' => Types::INTEGER,
                'date' => Types::DATE_IMMUTABLE,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }

    public function existsForProAndDate(int $proId, \DateTimeImmutable $date): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT 1 FROM off_full_day WHERE pro_id = :pro_id AND date = :date',
            [
                'pro_id' => $proId,
                'date' => $date,
            ],
            [
                'pro_id' => Types::INTEGER,
                'date' => Types::DATE_IMMUTABLE,
            ]
        );

        return $result !== false;
    }

    public function deleteForProAndDate(int $proId, \DateTimeImmutable $date): void
    {
        $this->connection->executeStatement(
            'DELETE FROM off_full_day WHERE pro_id = :pro_id AND date = :date',
            [
                'pro_id' => $proId,
                'date' => $date,
            ],
            [
                'pro_id' => Types::INTEGER,
                'date' => Types::DATE_IMMUTABLE,
            ]
        );
    }
}
