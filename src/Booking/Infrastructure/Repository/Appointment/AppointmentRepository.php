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

    public function createFromClient(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $startDateTimeUtc,
        string $lastName,
        string $firstName,
        string $email
    ): int {
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
            'INSERT INTO appointment (pro_id, service_id, start_dt, end_dt, last_name, first_name, email)
             VALUES (:pro_id, :service_id, :start_dt, :end_dt, :last_name, :first_name, :email)',
            [
                'pro_id' => $proId,
                'service_id' => $serviceId,
                'start_dt' => $startDateTimeUtc,
                'end_dt' => $endDateTimeUtc,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
            ],
            [
                'pro_id' => Types::INTEGER,
                'service_id' => Types::INTEGER,
                'start_dt' => Types::DATETIME_IMMUTABLE,
                'end_dt' => Types::DATETIME_IMMUTABLE,
                'last_name' => Types::STRING,
                'first_name' => Types::STRING,
                'email' => Types::STRING,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }
}
