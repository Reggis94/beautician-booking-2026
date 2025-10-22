<?php

namespace App\Dto;

class AvailabilityDto
{
    private string $dateLocal = '';
    private string $startTime = '';
    private string $endTime = '';

    public function getDateLocal(): string
    {
        return $this->dateLocal;
    }

    public function setDateLocal(string $dateLocal): self
    {
        $this->dateLocal = $dateLocal;
        return $this;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }
}

