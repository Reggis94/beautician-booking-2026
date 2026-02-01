<?php

namespace App\Availability\Application\Dto;

final class OffFullDayDto
{
    public function __construct(
        private int $proId,
        private \DateTimeImmutable $date
    ) {
        $this->assertValid();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $proId = isset($payload['proId']) ? (int) $payload['proId'] : 0;
        $dateValue = $payload['date'] ?? null;

        if (!is_string($dateValue) || $dateValue === '') {
            throw new \InvalidArgumentException('date must be a non-empty string.');
        }

        try {
            $date = new \DateTimeImmutable($dateValue);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('date must be a valid date string.');
        }

        return new self($proId, $date);
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
            throw new \InvalidArgumentException('proId must be a positive integer.');
        }
    }
}
