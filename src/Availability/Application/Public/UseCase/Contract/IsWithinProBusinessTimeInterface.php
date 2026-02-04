<?php

namespace App\Availability\Application\Public\UseCase\Contract;

interface IsWithinProBusinessTimeInterface
{
    /**
     * The $proLocalDateTime must be provided in the pro's local timezone.
     * 
     * NOTE [AVAIL-0001]: In the future, this use case may be replaced later by another which only checks availability 
     * in the future which will necessitates handling timezone
     */
    public function isWithinProBusinessTime(int $proId, \DateTimeImmutable $proLocalDateTime): bool;
}
