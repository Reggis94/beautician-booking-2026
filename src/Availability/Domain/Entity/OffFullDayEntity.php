<?php

namespace App\Availability\Domain\Entity;

use InvalidArgumentException;

final class OffFullDayEntity
{
    private \DateTimeImmutable $date;

    public function __construct(
        private int $proId,
        \DateTimeImmutable $date
    ) {
        $this->date = $date->setTime(0, 0);
        $this->assertValid();
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    private function assertValid(): void
    {
        if ($this->proId <= 0) {
            throw new InvalidArgumentException('proId must be a positive integer.');
        }

        if ($this->date->format('H:i') !== '00:00') {
            throw new InvalidArgumentException('date must be a calendar day without time.');
        }
    }
}
