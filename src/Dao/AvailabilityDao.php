<?php

namespace App\Dao;

use App\Dto\AvailabilityDto;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class AvailabilityDao
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function softDelete(int $proId, string $dateLocal): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateLocal) ?: new \DateTimeImmutable($dateLocal);
        $this->connection->executeStatement(
            'UPDATE availability SET deleted_at = NOW() WHERE pro_id = :pro_id AND date_local = :date_local AND deleted_at IS NULL',
            [
                'pro_id' => $proId,
                'date_local' => $date,
            ],
            [
                'pro_id' => Types::INTEGER,
                'date_local' => Types::DATE_IMMUTABLE,
            ]
        );
    }

    public function findActiveByProAndDate(int $proId, string $dateLocal): ?AvailabilityDto
    {
        $sql = 'SELECT start_at, end_at FROM availability WHERE pro_id = :pro_id AND date_local = :date_local AND deleted_at IS NULL LIMIT 1';
        $row = $this->connection->fetchAssociative($sql, [
            'pro_id' => $proId,
            'date_local' => $dateLocal,
        ]);

        if (!$row) {
            return null;
        }

        $dto = new AvailabilityDto();
        $dto->setDateLocal($dateLocal);

        // Format times as strings suitable for TimeType single_text
        $start = is_string($row['start_at']) ? new DateTimeImmutable($row['start_at']) : $row['start_at'];
        $end = is_string($row['end_at']) ? new DateTimeImmutable($row['end_at']) : $row['end_at'];

        $dto->setStartTime($start->format('H:i'));
        $dto->setEndTime($end->format('H:i'));

        return $dto;
    }

    public function upsert(int $proId, AvailabilityDto $availability): void
    {
        $dateLocalStr = $availability->getDateLocal();
        $dateLocal = DateTimeImmutable::createFromFormat('Y-m-d', $dateLocalStr) ?: new DateTimeImmutable($dateLocalStr);

        $startAt = $this->combineLocalDateTime($dateLocalStr, $availability->getStartTime());
        $endAt = $this->combineLocalDateTime($dateLocalStr, $availability->getEndTime());

        $sql = <<<SQL
WITH upsert AS (
    UPDATE availability
       SET start_at = :start_at,
           end_at   = :end_at,
           deleted_at = NULL
     WHERE pro_id = :pro_id
       AND date_local = :date_local
       AND deleted_at IS NULL
     RETURNING id
)
INSERT INTO availability (pro_id, date_local, start_at, end_at)
SELECT :pro_id, :date_local, :start_at, :end_at
WHERE NOT EXISTS (SELECT 1 FROM upsert)
SQL;

        $this->connection->executeStatement(
            $sql,
            [
                'pro_id'     => $proId,
                'date_local' => $dateLocal,
                'start_at'   => $startAt,
                'end_at'     => $endAt,
            ],
            [
                'pro_id'     => Types::INTEGER,
                'date_local' => Types::DATE_IMMUTABLE,
                'start_at'   => Types::DATETIME_IMMUTABLE,
                'end_at'     => Types::DATETIME_IMMUTABLE,
            ]
        );
    }

    private function combineLocalDateTime(string $date, string $time): DateTimeImmutable
    {
        $candidates = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];
        foreach ($candidates as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, "$date $time");
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
        }
        return new DateTimeImmutable("$date $time");
    }
}
