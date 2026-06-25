<?php

namespace App\Availability\Public\Query;

interface ServiceIsWithinProBusinessTimeQueryInterface
{
    /**
     * The $proLocalDateTime must be provided in the pro's local timezone.
     */
    public function isServiceWithinProBusinessTime(
        int $proId,
        int $serviceId,
        string $proLocalDateTime
    ): bool;
}
