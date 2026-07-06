<?php

namespace App\Availability\Domain\Repository;

use App\Availability\Domain\Entity\AvailabilityEntity;

interface AvailabilityRepositoryInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function create(AvailabilityEntity $availability): int;

    /**
     * @return array<int, AvailabilityEntity>
     */
    public function findOverlaps(
        int $proId,
        \DateTimeImmutable $weekStartDate,
        \DateTimeImmutable $weekEndDate
    ): array;

    public function deleteOverlaps(
        int $proId,
        \DateTimeImmutable $weekStartDate,
        \DateTimeImmutable $weekEndDate
    ): void;

    /**
     * @return array<int, array{date: string, startTimeLocal: string, endTimeLocal: string}>
     */
    public function getAvailabilitiesForMonth(int $proId, \DateTimeImmutable $monthStart): array;

    /**
     * @return array<int, string>
     */
    public function getBookableDaysForService(
        int $serviceId,
        \DateTimeImmutable $monthStart
    ): array;

    /**
     * Returns local bookable start ranges where end_time is the latest valid start time.
     *
     * @return array<int, array{date: string, start_time: string, end_time: string, duration_min: int}>
     */
    public function getBookableFreeRangesForServiceAndDay(
        int $serviceId,
        \DateTimeImmutable $localDate
    ): array;

    public function isServiceWithinProBusinessTime(
        int $proId,
        int $serviceId,
        string $proLocalDateTime
    ): bool;
}
