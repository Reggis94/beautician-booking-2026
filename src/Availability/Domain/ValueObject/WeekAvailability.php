<?php

namespace App\Availability\Domain\ValueObject;

use App\Availability\Domain\Entity\AvailabilityEntity;
use InvalidArgumentException;

final class WeekAvailability
{
    public function __construct(
        private int $proId,
        private \DateTimeImmutable $startDateTime,
        private \DateTimeImmutable $endDateTime,
        private int $dayOfWeek,
        private string $startTime,
        private string $endTime
    ) {
        $this->assertValid();
    }

    public static function fromAvailabilityEntity(AvailabilityEntity $availability, int $proId): self
    {
        return new self(
            $proId,
            $availability->getWeekStartDate()->setTime(0, 0),
            $availability->getWeekEndDate()->setTime(23, 59),
            $availability->getDayOfWeek(),
            $availability->getStartTime()->format('H:i'),
            $availability->getEndTime()->format('H:i')
        );
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function setProId(int $proId): void
    {
        $this->proId = $proId;
        $this->assertValid();
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getStartDateTime(): \DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTimeImmutable $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
        $this->assertValid();
    }

    public function getEndDateTime(): \DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(\DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
        $this->assertValid();
    }

    public function setDayOfWeek(int $dayOfWeek): void
    {
        $this->dayOfWeek = $dayOfWeek;
        $this->assertValid();
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
        $this->assertValid();
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
        $this->assertValid();
    }

    private function assertValid(): void
    {
        // NOTE: Week boundary rules are duplicated in AvailabilityEntity; update both if they change.
        if ($this->proId <= 0) {
            throw new InvalidArgumentException('proId must be a positive integer.');
        }

        if ($this->dayOfWeek < 1 || $this->dayOfWeek > 7) {
            throw new InvalidArgumentException('dayOfWeek must be between 1 and 7.');
        }

        if ($this->startDateTime->format('N') !== '1') {
            throw new InvalidArgumentException('startDateTime must be a Monday.');
        }

        if ($this->endDateTime->format('N') !== '7') {
            throw new InvalidArgumentException('endDateTime must be a Sunday.');
        }

        if ($this->startDateTime->format('H:i') !== '00:00') {
            throw new InvalidArgumentException('startDateTime time must be 00:00.');
        }

        if ($this->endDateTime->format('H:i') !== '23:59') {
            throw new InvalidArgumentException('endDateTime time must be 23:59.');
        }

        if ($this->endDateTime < $this->startDateTime) {
            throw new InvalidArgumentException('endDateTime must be after startDateTime.');
        }

        if (!$this->isValidTime($this->startTime)) {
            throw new InvalidArgumentException('startTime must be in HH:MM format.');
        }

        if (!$this->isValidTime($this->endTime)) {
            throw new InvalidArgumentException('endTime must be in HH:MM format.');
        }

        if ($this->toMinutes($this->endTime) <= $this->toMinutes($this->startTime)) {
            throw new InvalidArgumentException('endTime must be after startTime.');
        }
    }

    private function isValidTime(string $value): bool
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return false;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value, 2));

        return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59;
    }

    private function toMinutes(string $value): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $value, 2));

        return ($hour * 60) + $minute;
    }
}
