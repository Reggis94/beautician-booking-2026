<?php

namespace App\Availability\Infrastructure\Repository;

use App\Availability\Domain\Entity\AvailabilityEntity;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class AvailabilityRepository implements AvailabilityRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    public function findOverlaps(int $proId, \DateTimeImmutable $weekStartDate, \DateTimeImmutable $weekEndDate): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT pro_id, week_start_date, week_end_date, day_of_week, start_time, end_time
             FROM availability
             WHERE pro_id = :pro_id
               AND week_start_date <= :week_end_date
               AND week_end_date >= :week_start_date',
            [
                'pro_id' => $proId,
                'week_start_date' => $weekStartDate,
                'week_end_date' => $weekEndDate,
            ],
            [
                'pro_id' => Types::INTEGER,
                'week_start_date' => Types::DATE_IMMUTABLE,
                'week_end_date' => Types::DATE_IMMUTABLE,
            ]
        );

        $overlaps = [];
        foreach ($rows as $row) {
            $weekStart = $row['week_start_date'] instanceof \DateTimeImmutable
                ? $row['week_start_date']
                : new \DateTimeImmutable((string) $row['week_start_date']);
            $weekEnd = $row['week_end_date'] instanceof \DateTimeImmutable
                ? $row['week_end_date']
                : new \DateTimeImmutable((string) $row['week_end_date']);
            $startTime = $row['start_time'] instanceof \DateTimeImmutable
                ? $row['start_time']
                : new \DateTimeImmutable((string) $row['start_time']);
            $endTime = $row['end_time'] instanceof \DateTimeImmutable
                ? $row['end_time']
                : new \DateTimeImmutable((string) $row['end_time']);

            $overlaps[] = new AvailabilityEntity(
                (int) $row['pro_id'],
                $weekStart,
                $weekEnd,
                (int) $row['day_of_week'],
                $startTime,
                $endTime
            );
        }

        return $overlaps;
    }

    public function deleteOverlaps(int $proId, \DateTimeImmutable $weekStartDate, \DateTimeImmutable $weekEndDate): void
    {
        $this->connection->executeStatement(
            'DELETE FROM availability
             WHERE pro_id = :pro_id
               AND week_start_date <= :week_end_date
               AND week_end_date >= :week_start_date',
            [
                'pro_id' => $proId,
                'week_start_date' => $weekStartDate,
                'week_end_date' => $weekEndDate,
            ],
            [
                'pro_id' => Types::INTEGER,
                'week_start_date' => Types::DATE_IMMUTABLE,
                'week_end_date' => Types::DATE_IMMUTABLE,
            ]
        );
    }

    public function findCoveringDate(int $proId, \DateTimeImmutable $date): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT pro_id, week_start_date, week_end_date, day_of_week, start_time, end_time
             FROM availability
             WHERE pro_id = :pro_id
               AND week_start_date <= :date
               AND week_end_date >= :date',
            [
                'pro_id' => $proId,
                'date' => $date,
            ],
            [
                'pro_id' => Types::INTEGER,
                'date' => Types::DATE_IMMUTABLE,
            ]
        );

        $covering = [];
        foreach ($rows as $row) {
            $weekStart = $row['week_start_date'] instanceof \DateTimeImmutable
                ? $row['week_start_date']
                : new \DateTimeImmutable((string) $row['week_start_date']);
            $weekEnd = $row['week_end_date'] instanceof \DateTimeImmutable
                ? $row['week_end_date']
                : new \DateTimeImmutable((string) $row['week_end_date']);
            $startTime = $row['start_time'] instanceof \DateTimeImmutable
                ? $row['start_time']
                : new \DateTimeImmutable((string) $row['start_time']);
            $endTime = $row['end_time'] instanceof \DateTimeImmutable
                ? $row['end_time']
                : new \DateTimeImmutable((string) $row['end_time']);

            $covering[] = new AvailabilityEntity(
                (int) $row['pro_id'],
                $weekStart,
                $weekEnd,
                (int) $row['day_of_week'],
                $startTime,
                $endTime
            );
        }

        return $covering;
    }

    public function create(AvailabilityEntity $availability): int
    {
        $this->connection->executeStatement(
            'INSERT INTO availability (pro_id, week_start_date, week_end_date, day_of_week, start_time, end_time)
             VALUES (:pro_id, :week_start_date, :week_end_date, :day_of_week, :start_time, :end_time)',
            [
                'pro_id' => $availability->getProId(),
                'week_start_date' => $availability->getWeekStartDate(),
                'week_end_date' => $availability->getWeekEndDate(),
                'day_of_week' => $availability->getDayOfWeek(),
                'start_time' => $availability->getStartTime(),
                'end_time' => $availability->getEndTime(),
            ],
            [
                'pro_id' => Types::INTEGER,
                'week_start_date' => Types::DATE_IMMUTABLE,
                'week_end_date' => Types::DATE_IMMUTABLE,
                'day_of_week' => Types::SMALLINT,
                'start_time' => Types::TIME_IMMUTABLE,
                'end_time' => Types::TIME_IMMUTABLE,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }
}
