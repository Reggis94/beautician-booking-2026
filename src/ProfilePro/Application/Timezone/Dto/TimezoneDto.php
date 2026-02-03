<?php

namespace App\ProfilePro\Application\Timezone\Dto;

final class TimezoneDto
{
    private int $proId;
    private string $fullAddress;
    private float $lat;
    private float $lng;
    public function __construct(int $proId, string $fullAddress, float $lat, float $lng)
    {
        if ($proId <= 0) {
            throw new \InvalidArgumentException('pro_id must be a positive integer.');
        }

        $fullAddress = trim($fullAddress);
        if ($fullAddress === '') {
            throw new \InvalidArgumentException('full_address must be a non-empty string.');
        }

        // Invariant duplicated with UpdateTimezoneCommandHandler: if you change lat/lng bounds here, update the handler too.
        if ($lat < -90.0 || $lat > 90.0) {
            throw new \InvalidArgumentException('lat must be between -90 and 90.');
        }

        // Invariant duplicated with UpdateTimezoneCommandHandler: if you change lat/lng bounds here, update the handler too.
        if ($lng < -180.0 || $lng > 180.0) {
            throw new \InvalidArgumentException('lng must be between -180 and 180.');
        }

        $this->proId = $proId;
        $this->fullAddress = $fullAddress;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getFullAddress(): string
    {
        return $this->fullAddress;
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }
}
