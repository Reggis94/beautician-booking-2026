<?php

namespace App\Booking\Infrastructure\Repository\Appointment;

use App\Booking\Application\Repository\Appointment\OverlapRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class OverlapRepository implements OverlapRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function doesServiceBelongsToPro(int $serviceId, int $proId): bool
    {
        $sql = 'SELECT 1
                FROM service
                WHERE id = :service_id
                  AND pro_id = :pro_id
                LIMIT 1';

        return (bool) $this->connection->fetchOne(
            $sql,
            [
                'service_id' => $serviceId,
                'pro_id' => $proId,
            ],
            [
                'service_id' => Types::INTEGER,
                'pro_id' => Types::INTEGER,
            ]
        );

    }

    public function existsOverlappingAppointment(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $datetimeStart
    ): bool {
        if ($datetimeStart->getTimezone()->getName() !== 'UTC') {
            throw new \InvalidArgumentException('datetimeStart must be in UTC.');
        }

        if (! $this->doesServiceBelongsToPro($serviceId, $proId)) {
            throw new \LogicException('Service does not belong to the pro.');
        }

        // Appointment overlap checks compare UTC instants.
        // See docs/adr/0013-datetime-timezone-policy.md.
        $sql = 'SELECT 1
                FROM appointment a
                INNER JOIN service requested_service ON requested_service.id = :service_id
                WHERE a.pro_id = :pro_id
                  AND a.deleted_at IS NULL
                  AND requested_service.pro_id = :pro_id
                  AND tsrange(
                        a.start_dt,
                        COALESCE(a.end_dt, a.start_dt),
                        \'[)\'
                      ) &&
                      tsrange(
                        :start_dt::timestamp,
                        :start_dt::timestamp + make_interval(mins => COALESCE(requested_service.duration_min, 1)),
                        \'[)\'
                      )
                LIMIT 1';

        return (bool) $this->connection->fetchOne(
            $sql,
            [
                'pro_id' => $proId,
                'service_id' => $serviceId,
                'start_dt' => $datetimeStart->format('Y-m-d H:i:s'),
            ],
            [
                'pro_id' => Types::INTEGER,
                'service_id' => Types::INTEGER,
                'start_dt' => Types::STRING,
            ]
        );
    }
}
