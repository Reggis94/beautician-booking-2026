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

    /**
     * @return array<int, string>
     */
    public function getBookableDaysForService(
        int $serviceId,
        \DateTimeImmutable $monthStart
    ): array {
        // Future improvement: for today, compare free ranges against the pro's current local timestamp.
        // See docs/future-improvements/availability/filter-past-bookable-ranges-for-today.md.
        $rows = $this->connection->fetchAllAssociative(
            "WITH requested_service AS (
                SELECT id, pro_id, COALESCE(duration_min, 1) AS duration_min
                FROM service
                WHERE id = :service_id
                  AND deleted_at IS NULL
            ),
            pro_context AS (
                SELECT p.id AS pro_id, COALESCE(p.timezone_iana, 'UTC') AS timezone_iana, rs.duration_min
                FROM pro p
                INNER JOIN requested_service rs ON rs.pro_id = p.id
                WHERE p.deleted_at IS NULL
            ),
            month_bounds AS (
                SELECT CAST(:month_start AS date) AS month_start,
                       (CAST(:month_start AS date) + INTERVAL '1 month')::date AS month_end
            ),
            availability_windows AS (
                SELECT pc.pro_id,
                       pc.timezone_iana,
                       concrete_day::date AS local_date,
                       ((concrete_day::date + av.start_time) AT TIME ZONE pc.timezone_iana) AS window_start,
                       ((concrete_day::date + av.end_time) AT TIME ZONE pc.timezone_iana) AS window_end,
                       pc.duration_min
                FROM availability av
                CROSS JOIN pro_context pc
                CROSS JOIN month_bounds mb
                CROSS JOIN LATERAL generate_series(
                    GREATEST(av.week_start_date, mb.month_start),
                    LEAST(av.week_end_date, mb.month_end - 1),
                    INTERVAL '1 day'
                ) AS concrete_day
                WHERE av.pro_id = pc.pro_id
                  AND EXTRACT(ISODOW FROM concrete_day)::smallint = av.day_of_week
            ),
            overlapping_appointments AS (
                SELECT aw.local_date,
                       aw.window_start,
                       aw.window_end,
                       aw.duration_min,
                       GREATEST(a.start_dt, aw.window_start) AS busy_start,
                       LEAST(
                           COALESCE(
                               a.end_dt,
                               a.start_dt + make_interval(
                                   mins => COALESCE(booked_service.duration_min, aw.duration_min)
                               )
                           ),
                           aw.window_end
                       ) AS busy_end
                FROM availability_windows aw
                INNER JOIN appointment a ON a.pro_id = aw.pro_id
                  AND a.deleted_at IS NULL
                LEFT JOIN service booked_service ON booked_service.id = a.service_id
                WHERE tstzrange(
                    a.start_dt,
                    COALESCE(
                        a.end_dt,
                        a.start_dt + make_interval(
                            mins => COALESCE(booked_service.duration_min, aw.duration_min)
                        )
                    ),
                    '[)'
                ) && tstzrange(aw.window_start, aw.window_end, '[)')
            ),
            clipped_appointments AS (
                SELECT local_date, window_start, window_end, duration_min, busy_start, busy_end
                FROM overlapping_appointments
                WHERE busy_end > busy_start
            ),
            ordered_appointments AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       busy_start,
                       busy_end,
                       MAX(busy_end) OVER (
                           PARTITION BY local_date, window_start, window_end
                           ORDER BY busy_start, busy_end
                           ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                       ) AS previous_busy_end
                FROM clipped_appointments
            ),
            appointment_groups AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       busy_start,
                       busy_end,
                       SUM(
                           CASE
                               WHEN previous_busy_end IS NULL OR busy_start > previous_busy_end THEN 1
                               ELSE 0
                           END
                       ) OVER (
                           PARTITION BY local_date, window_start, window_end
                           ORDER BY busy_start, busy_end
                       ) AS group_number
                FROM ordered_appointments
            ),
            merged_appointments AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       MIN(busy_start) AS busy_start,
                       MAX(busy_end) AS busy_end
                FROM appointment_groups
                GROUP BY local_date, window_start, window_end, duration_min, group_number
            ),
            free_ranges AS (
                SELECT aw.local_date,
                       aw.timezone_iana,
                       aw.duration_min,
                       aw.window_start AS free_start,
                       COALESCE(MIN(ma.busy_start), aw.window_end) AS free_end
                FROM availability_windows aw
                LEFT JOIN merged_appointments ma ON ma.local_date = aw.local_date
                  AND ma.window_start = aw.window_start
                  AND ma.window_end = aw.window_end
                GROUP BY aw.local_date, aw.timezone_iana, aw.duration_min, aw.window_start, aw.window_end
                UNION ALL
                SELECT ma.local_date,
                       pc.timezone_iana,
                       ma.duration_min,
                       ma.busy_end AS free_start,
                       COALESCE(
                           LEAD(ma.busy_start) OVER (
                               PARTITION BY ma.local_date, ma.window_start, ma.window_end
                               ORDER BY ma.busy_start, ma.busy_end
                           ),
                           ma.window_end
                       ) AS free_end
                FROM merged_appointments ma
                CROSS JOIN pro_context pc
            )
            SELECT DISTINCT local_date::text AS date
            FROM free_ranges
            WHERE free_end > free_start
              AND local_date >= (NOW() AT TIME ZONE timezone_iana)::date
              AND EXTRACT(EPOCH FROM (free_end - free_start)) / 60 >= duration_min
            ORDER BY date",
            [
                'service_id' => $serviceId,
                'month_start' => $monthStart,
            ],
            [
                'service_id' => Types::INTEGER,
                'month_start' => Types::DATE_IMMUTABLE,
            ]
        );

        return array_map(
            static fn (array $row): string => (string) $row['date'],
            $rows
        );
    }

    /**
     * @return array<int, array{date: string, start_time: string, end_time: string, duration_min: int}>
     */
    public function getBookableFreeRangesForServiceAndDay(
        int $serviceId,
        \DateTimeImmutable $localDate
    ): array {
        $rows = $this->connection->fetchAllAssociative(
            "WITH requested_service AS (
                SELECT id, pro_id, COALESCE(duration_min, 1) AS duration_min
                FROM service
                WHERE id = :service_id
                  AND deleted_at IS NULL
            ),
            pro_context AS (
                SELECT p.id AS pro_id,
                       COALESCE(p.timezone_iana, 'UTC') AS timezone_iana,
                       rs.duration_min
                FROM pro p
                INNER JOIN requested_service rs ON rs.pro_id = p.id
                WHERE p.deleted_at IS NULL
            ),
            requested_day AS (
                SELECT CAST(:local_date AS date) AS local_date
            ),
            availability_windows AS (
                SELECT pc.pro_id,
                       pc.timezone_iana,
                       rd.local_date,
                       ((rd.local_date + av.start_time) AT TIME ZONE pc.timezone_iana) AS window_start,
                       ((rd.local_date + av.end_time) AT TIME ZONE pc.timezone_iana) AS window_end,
                       pc.duration_min
                FROM availability av
                CROSS JOIN pro_context pc
                CROSS JOIN requested_day rd
                WHERE av.pro_id = pc.pro_id
                  AND av.week_start_date <= rd.local_date
                  AND av.week_end_date >= rd.local_date
                  AND EXTRACT(ISODOW FROM rd.local_date)::smallint = av.day_of_week
            ),
            overlapping_appointments AS (
                SELECT aw.local_date,
                       aw.window_start,
                       aw.window_end,
                       aw.duration_min,
                       GREATEST(a.start_dt, aw.window_start) AS busy_start,
                       LEAST(
                           COALESCE(
                               a.end_dt,
                               a.start_dt + make_interval(
                                   mins => COALESCE(booked_service.duration_min, aw.duration_min)
                               )
                           ),
                           aw.window_end
                       ) AS busy_end
                FROM availability_windows aw
                INNER JOIN appointment a ON a.pro_id = aw.pro_id
                  AND a.deleted_at IS NULL
                LEFT JOIN service booked_service ON booked_service.id = a.service_id
                WHERE tstzrange(
                    a.start_dt,
                    COALESCE(
                        a.end_dt,
                        a.start_dt + make_interval(
                            mins => COALESCE(booked_service.duration_min, aw.duration_min)
                        )
                    ),
                    '[)'
                ) && tstzrange(aw.window_start, aw.window_end, '[)')
            ),
            clipped_appointments AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       busy_start,
                       busy_end
                FROM overlapping_appointments
                WHERE busy_end > busy_start
            ),
            ordered_appointments AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       busy_start,
                       busy_end,
                       MAX(busy_end) OVER (
                           PARTITION BY local_date, window_start, window_end
                           ORDER BY busy_start, busy_end
                           ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                       ) AS previous_busy_end
                FROM clipped_appointments
            ),
            appointment_groups AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       busy_start,
                       busy_end,
                       SUM(
                           CASE
                               WHEN previous_busy_end IS NULL OR busy_start > previous_busy_end THEN 1
                               ELSE 0
                           END
                       ) OVER (
                           PARTITION BY local_date, window_start, window_end
                           ORDER BY busy_start, busy_end
                       ) AS group_number
                FROM ordered_appointments
            ),
            merged_appointments AS (
                SELECT local_date,
                       window_start,
                       window_end,
                       duration_min,
                       MIN(busy_start) AS busy_start,
                       MAX(busy_end) AS busy_end
                FROM appointment_groups
                GROUP BY local_date, window_start, window_end, duration_min, group_number
            ),
            free_ranges AS (
                SELECT aw.local_date,
                       aw.timezone_iana,
                       aw.duration_min,
                       aw.window_start AS free_start,
                       COALESCE(MIN(ma.busy_start), aw.window_end) AS free_end
                FROM availability_windows aw
                LEFT JOIN merged_appointments ma ON ma.local_date = aw.local_date
                  AND ma.window_start = aw.window_start
                  AND ma.window_end = aw.window_end
                GROUP BY aw.local_date,
                         aw.timezone_iana,
                         aw.duration_min,
                         aw.window_start,
                         aw.window_end
                UNION ALL
                SELECT ma.local_date,
                       pc.timezone_iana,
                       ma.duration_min,
                       ma.busy_end AS free_start,
                       COALESCE(
                           LEAD(ma.busy_start) OVER (
                               PARTITION BY ma.local_date, ma.window_start, ma.window_end
                               ORDER BY ma.busy_start, ma.busy_end
                           ),
                           ma.window_end
                       ) AS free_end
                FROM merged_appointments ma
                CROSS JOIN pro_context pc
            )
            SELECT local_date::text AS date,
                   to_char(free_start AT TIME ZONE timezone_iana, 'HH24:MI') AS start_time,
                   to_char(
                       (free_end - make_interval(mins => duration_min)) AT TIME ZONE timezone_iana,
                       'HH24:MI'
                   ) AS end_time,
                   FLOOR(EXTRACT(EPOCH FROM (free_end - free_start)) / 60)::int AS duration_min
            FROM free_ranges
            WHERE free_end > free_start
              AND local_date >= (NOW() AT TIME ZONE timezone_iana)::date
              AND EXTRACT(EPOCH FROM (free_end - free_start)) / 60 >= duration_min
            ORDER BY free_start",
            [
                'service_id' => $serviceId,
                'local_date' => $localDate,
            ],
            [
                'service_id' => Types::INTEGER,
                'local_date' => Types::DATE_IMMUTABLE,
            ]
        );

        return array_map(
            static fn (array $row): array => [
                'date' => (string) $row['date'],
                'start_time' => (string) $row['start_time'],
                'end_time' => (string) $row['end_time'],
                'duration_min' => (int) $row['duration_min'],
            ],
            $rows
        );
    }

    public function isWithinProBusinessTime(int $proId, \DateTimeImmutable $proLocalDateTime): bool
    {
        $dayOfWeek = (int) $proLocalDateTime->format('N');
        $localDate = $proLocalDateTime->setTime(0, 0);
        $localTime = new \DateTimeImmutable($proLocalDateTime->format('H:i:s'));

        $result = $this->connection->fetchOne(
            'SELECT 1
             FROM availability
             WHERE pro_id = :pro_id
               AND week_start_date <= :local_date
               AND week_end_date >= :local_date
               AND day_of_week = :day_of_week
               AND start_time <= :local_time
               AND end_time > :local_time
             LIMIT 1',
            [
                'pro_id' => $proId,
                'local_date' => $localDate,
                'day_of_week' => $dayOfWeek,
                'local_time' => $localTime,
            ],
            [
                'pro_id' => Types::INTEGER,
                'local_date' => Types::DATE_IMMUTABLE,
                'day_of_week' => Types::SMALLINT,
                'local_time' => Types::TIME_IMMUTABLE,
            ]
        );

        return $result !== false;
    }
}
