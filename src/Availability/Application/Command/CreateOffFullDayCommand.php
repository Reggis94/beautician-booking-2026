<?php

namespace App\Availability\Domain\Application\Command;

final readonly class CreateOffFullDayCommand
{
    public function __construct(
        private int $proId,
        private \DateTimeImmutable $date
    ) {
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}
