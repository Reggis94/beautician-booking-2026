<?php

namespace App\Availability\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Table(name: 'availability')]
#[ORM\Entity]
class AvailabilityEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $proId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekStartDate;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekEndDate;

    #[ORM\Column(type: 'smallint')]
    private int $dayOfWeek;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $endTime;

    public function __construct(
        int $proId,
        \DateTimeImmutable $weekStartDate,
        \DateTimeImmutable $weekEndDate,
        int $dayOfWeek,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ) {
        $this->proId = $proId;
        $this->weekStartDate = $weekStartDate;
        $this->weekEndDate = $weekEndDate;
        $this->dayOfWeek = $dayOfWeek;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->assertValid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function setProId(int $proId): self
    {
        $this->proId = $proId;
        return $this;
    }

    public function getWeekStartDate(): \DateTimeImmutable
    {
        return $this->weekStartDate;
    }

    public function setWeekStartDate(\DateTimeImmutable $weekStartDate): self
    {
        $this->weekStartDate = $weekStartDate;
        $this->assertValid();
        return $this;
    }

    public function getWeekEndDate(): \DateTimeImmutable
    {
        return $this->weekEndDate;
    }

    public function setWeekEndDate(\DateTimeImmutable $weekEndDate): self
    {
        $this->weekEndDate = $weekEndDate;
        $this->assertValid();
        return $this;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    // NOTE: Week boundary rules are duplicated in WeekAvailability; update both if they change.
    private function assertValid(): void
    {
        if ($this->weekStartDate->format('N') !== '1') {
            throw new InvalidArgumentException('weekStartDate must be a Monday.');
        }

        if ($this->weekEndDate->format('N') !== '7') {
            throw new InvalidArgumentException('weekEndDate must be a Sunday.');
        }

        if ($this->weekEndDate < $this->weekStartDate) {
            throw new InvalidArgumentException('weekEndDate must be after weekStartDate.');
        }

        if ($this->endTime <= $this->startTime) {
            throw new InvalidArgumentException('endTime must be after startTime.');
        }
    }
}
