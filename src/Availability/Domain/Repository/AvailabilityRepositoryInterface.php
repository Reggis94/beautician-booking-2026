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
     * @return array<int, AvailabilityEntity>
     */
    public function findCoveringDate(int $proId, \DateTimeImmutable $date): array;
}
