<?php

namespace App\Availability\Public\Query;

interface IsWithinProBusinessTimeQueryInterface
{
    /**
     * The $proLocalDateTime must be provided in the pro's local timezone.
     *
     * NOTE [AVAIL-0001]: In the future, this query may be replaced later by another which only checks availability
     * in the future which will necessitates handling timezone.
     *
     * @see docs/future-improvements/availability/avail-0001-is-within-pro-hours
     */
    public function isWithinProBusinessTime(int $proId, \DateTimeImmutable $proLocalDateTime): bool;
}
