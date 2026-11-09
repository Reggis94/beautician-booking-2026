<?php

namespace App\Booking\Application\Repository\Appointment;

interface OverlapRepositoryInterface
{
    /**
     * Returns true when the service belongs to the pro and can be booked: published and not deleted
     * (not a draft, not deleted, not replaced by a newer version).
     */
    public function doesServiceBelongsToPro(int $serviceId, int $proId): bool;

    /**
     * @see docs/future-improvements/booking/book-0001-exist-overlap-only-future
     *
     * datetimeStart must be in UTC time
     *
     * @throws \InvalidArgumentException When datetimeStart is not UTC.
     * @throws \App\Booking\Application\Exception\ServiceDoesNotBelongToProException When the service does not
     *     belong to the pro or cannot be booked any more.
     */
    public function existsOverlappingAppointment(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $datetimeStart
    ): bool;
}
