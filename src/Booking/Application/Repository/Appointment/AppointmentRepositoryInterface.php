<?php

namespace App\Booking\Application\Repository\Appointment;

interface AppointmentRepositoryInterface
{
    public function createFromClient(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $startDateTimeUtc,
        string $lastName,
        string $firstName,
        string $email
    ): void;
}
