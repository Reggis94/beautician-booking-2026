<?php

namespace App\Availability\Domain\Application\Command;

use App\Availability\Domain\ValueObject\WeekAvailability;

final readonly class CreateWeekAvailabilityCommand
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
