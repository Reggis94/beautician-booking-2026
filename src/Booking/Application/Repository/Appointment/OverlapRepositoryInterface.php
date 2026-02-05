<?php

namespace App\Booking\Application\Repository\Appointment;

interface OverlapRepositoryInterface
{
    /**
     * Returns true when the service belongs to the pro.
     */
    public function doesServiceBelongsToPro(int $serviceId, int $proId): bool;

    /**
     * @see docs/future-improvements/booking/book-0001-exist-overlap-only-future
     *
     * datetimeStart must be in UTC time
     *
     * @throws \InvalidArgumentException When datetimeStart is not UTC.
     * @throws \LogicException When the service does not belong to the pro.
     */
    public function existsOverlappingAppointment(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $datetimeStart
    ): bool;
}
