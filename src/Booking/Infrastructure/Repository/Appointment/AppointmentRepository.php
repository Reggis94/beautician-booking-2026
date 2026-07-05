<?php

namespace App\Booking\Infrastructure\Repository\Appointment;

use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class AppointmentRepository implements AppointmentRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    public function lockProAppointments(int $proId): void
    {
        $this->connection->fetchOne(
            'SELECT id FROM pro WHERE id = :pro_id AND deleted_at IS NULL FOR UPDATE',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminUpcomingAppointments(int $proId): array
    {
        return $this->listProAdminAppointments($proId, true);
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminPastAppointments(int $proId): array
    {
        return $this->listProAdminAppointments($proId, false);
    }

    public function createFromClient(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $startDateTimeUtc,
        string $lastName,
        string $firstName,
        string $email,
        string $extraPhone
    ): void {
        if ($startDateTimeUtc->getTimezone()->getName() !== 'UTC') {
            throw new \InvalidArgumentException('startDateTimeUtc must be in UTC.');
        }

        $durationMin = $this->connection->fetchOne(
            'SELECT duration_min FROM service WHERE id = :service_id',
            ['service_id' => $serviceId],
            ['service_id' => Types::INTEGER]
        );

        if ($durationMin === false) {
            throw new \RuntimeException('Service not found.');
        }

        $durationMinutes = max(1, (int) $durationMin);
        $endDateTimeUtc = $startDateTimeUtc->add(new \DateInterval('PT' . $durationMinutes . 'M'));

        $this->connection->executeStatement(
            'INSERT INTO appointment (pro_id, service_id, start_dt, end_dt, last_name, first_name, email, phone)
             VALUES (:pro_id, :service_id, :start_dt, :end_dt, :last_name, :first_name, :email, :phone)',
            [
                'pro_id' => $proId,
                'service_id' => $serviceId,
                'start_dt' => $startDateTimeUtc->format('Y-m-d H:i:s'),
                'end_dt' => $endDateTimeUtc->format('Y-m-d H:i:s'),
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'phone' => $extraPhone,
            ],
            [
                'pro_id' => Types::INTEGER,
                'service_id' => Types::INTEGER,
                'start_dt' => Types::STRING,
                'end_dt' => Types::STRING,
                'last_name' => Types::STRING,
                'first_name' => Types::STRING,
                'email' => Types::STRING,
                'phone' => Types::STRING,
            ]
        );
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    private function listProAdminAppointments(int $proId, bool $upcoming): array
    {
        $dateComparison = $upcoming ? 'a.start_dt >= NOW()' : 'a.start_dt < NOW()';
        $orderDirection = $upcoming ? 'ASC' : 'DESC';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT a.id,
                    TRIM(CONCAT(COALESCE(a.first_name, \'\'), \' \', COALESCE(a.last_name, \'\'))) AS client_name,
                    s.name AS service_name,
                    a.start_dt AS start_at,
                    CASE
                        WHEN a.end_dt IS NULL THEN s.duration_min
                        ELSE CAST(EXTRACT(EPOCH FROM (a.end_dt - a.start_dt)) / 60 AS INTEGER)
                    END AS duration_minutes
             FROM appointment a
             LEFT JOIN service s ON s.id = a.service_id AND s.pro_id = a.pro_id
             WHERE a.pro_id = :pro_id
               AND a.deleted_at IS NULL
               AND ' . $dateComparison . '
             ORDER BY a.start_dt ' . $orderDirection . ', a.id ' . $orderDirection,
            [
                'pro_id' => $proId,
            ],
            [
                'pro_id' => Types::INTEGER,
            ]
        );

        return array_map(
            static fn (array $row): array => [
                'client_name' => $row['client_name'] !== '' ? $row['client_name'] : 'Unknown client',
                'duration_minutes' => $row['duration_minutes'] !== null ? (int) $row['duration_minutes'] : null,
                'id' => (int) $row['id'],
                'service_name' => $row['service_name'],
                'start_at' => self::formatDateTime($row['start_at']),
            ],
            $rows
        );
    }

    private static function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return (new \DateTimeImmutable((string) $value))->format(\DateTimeInterface::ATOM);
    }
}
