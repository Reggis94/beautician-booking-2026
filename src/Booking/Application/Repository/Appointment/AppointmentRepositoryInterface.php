<?php

namespace App\Booking\Application\Repository\Appointment;

interface AppointmentRepositoryInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function lockProAppointments(int $proId): void;

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminUpcomingAppointments(int $proId): array;

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminPastAppointments(int $proId): array;

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
