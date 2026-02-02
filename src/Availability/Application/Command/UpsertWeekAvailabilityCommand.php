<?php

namespace App\Availability\Application\Command;

use App\Availability\Domain\ValueObject\WeekAvailability;

final readonly class UpsertWeekAvailabilityCommand
{
    public function __construct(
        /** @var array<int, WeekAvailability> */
        private array $newAvailabilities
    ) {
    }

    /** @return array<int, WeekAvailability> */
    public function getNewAvailabilities(): array
    {
        return $this->newAvailabilities;
    }
}
