<?php

namespace App\Availability\Domain\Repository;

use App\Availability\Domain\Entity\OffFullDayEntity;

interface OffFullDayRepositoryInterface
{
    public function create(OffFullDayEntity $offFullDay): int;

    public function existsForProAndDate(int $proId, \DateTimeImmutable $date): bool;

    public function deleteForProAndDate(int $proId, \DateTimeImmutable $date): void;
}
