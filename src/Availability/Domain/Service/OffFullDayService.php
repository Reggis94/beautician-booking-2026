<?php

namespace App\Availability\Domain\Service;

use App\Availability\Domain\Entity\OffFullDayEntity;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;
use App\Availability\Domain\Repository\OffFullDayRepositoryInterface;

final class OffFullDayService
{
    public function __construct(
        private AvailabilityRepositoryInterface $availabilityRepository,
        private OffFullDayRepositoryInterface $offFullDayRepository
    ) {
    }

    public function create(int $proId, \DateTimeImmutable $date): OffFullDayEntity
    {
        $date = $date->setTime(0, 0);

        if ($this->offFullDayRepository->existsForProAndDate($proId, $date)) {
            throw new \DomainException('Off full day already exists for this date.');
        }

        $coveringAvailabilities = $this->availabilityRepository->findCoveringDate($proId, $date);
        if ($coveringAvailabilities === []) {
            throw new \DomainException('Off full day must be within an existing availability range.');
        }

        $offFullDay = new OffFullDayEntity($proId, $date);
        $this->offFullDayRepository->create($offFullDay);

        return $offFullDay;
    }

    public function delete(int $proId, \DateTimeImmutable $date): void
    {
        $this->offFullDayRepository->deleteForProAndDate($proId, $date->setTime(0, 0));
    }
}
