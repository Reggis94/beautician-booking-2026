<?php

namespace App\Booking\Application\Repository\Appointment;

interface AppointmentRepositoryInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function lockProAppointments(int $proId): void;

    public function createFromClient(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $startDateTimeUtc,
        string $lastName,
        string $firstName,
        string $email,
        string $extraPhone
    ): void;
}
