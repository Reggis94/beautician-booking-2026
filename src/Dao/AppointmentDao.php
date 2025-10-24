<?php

namespace App\Dao;

use App\Dto\AppointmentDto;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class AppointmentDao
{
    public function __construct(private readonly Connection $connection) {}

    /**
     * @return array<int,string> id => name
     */
    public function getServicesForPro(int $proId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, name FROM service WHERE pro_id = :pro_id AND deleted_at IS NULL ORDER BY name',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );
        $choices = [];
        foreach ($rows as $row) {
            $choices[(int) $row['id']] = (string) $row['name'];
        }
        return $choices;
    }

    public function findOneForPro(int $proId, int $id): ?AppointmentDto
    {
        $sql = 'SELECT a.id, a.service_id, a.start_dt, a.end_dt, a.last_name, a.first_name, a.email
                FROM appointment a
                LEFT JOIN service s ON s.id = a.service_id
                WHERE a.id = :id AND (s.pro_id = :pro_id OR a.service_id IS NULL) AND a.deleted_at IS NULL';
        $row = $this->connection->fetchAssociative($sql, [
            'id' => $id,
            'pro_id' => $proId,
        ], [
            'id' => Types::INTEGER,
            'pro_id' => Types::INTEGER,
        ]);

        if (!$row) {
            return null;
        }

        $dto = new AppointmentDto();
        $dto->setId((int) $row['id']);
        $dto->setServiceId($row['service_id'] !== null ? (int) $row['service_id'] : null);
        $start = is_string($row['start_dt']) ? new DateTimeImmutable($row['start_dt']) : $row['start_dt'];
        // Use HTML5 datetime-local format for single_text DateTimeType
        $dto->setStartDt($start?->format('Y-m-d\TH:i'));
        if ($row['end_dt']) {
            $end = is_string($row['end_dt']) ? new DateTimeImmutable($row['end_dt']) : $row['end_dt'];
            if ($start && $end) {
                $dto->setDuration((int) round(($end->getTimestamp() - $start->getTimestamp()) / 60));
            }
        }
        $dto->setLastName($row['last_name']);
        $dto->setFirstName($row['first_name']);
        $dto->setEmail($row['email']);
        return $dto;
    }

    public function upsert(?int $id, int $proId, AppointmentDto $dto): int
    {
        $start = $dto->getStartDt();
        $startDt = $start ? (DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start) ?: new DateTimeImmutable($start)) : null;
        $endDt = null;
        if ($startDt && $dto->getDuration()) {
            $endDt = $startDt->add(new DateInterval('PT' . max(0, (int) $dto->getDuration()) . 'M'));
        }
        // If no end provided, default to start + 1 minute
        if ($startDt && $endDt === null) {
            $endDt = $startDt->add(new DateInterval('PT1M'));
        }
        if ($startDt && $this->hasOverlapForPro($proId, $startDt, $endDt, $id)) {
            throw new \RuntimeException('Appointment overlaps an existing booking.');
        }

        if ($id) {
            $this->connection->executeStatement(
                'UPDATE appointment SET service_id = :service_id, start_dt = :start_dt, end_dt = :end_dt,
                         last_name = :last_name, first_name = :first_name, email = :email
                 WHERE id = :id AND pro_id = :pro_id AND deleted_at IS NULL',
                [
                    'id' => $id,
                    'pro_id' => $proId,
                    'service_id' => $dto->getServiceId(),
                    'start_dt' => $startDt,
                    'end_dt' => $endDt,
                    'last_name' => $dto->getLastName(),
                    'first_name' => $dto->getFirstName(),
                    'email' => $dto->getEmail(),
                ],
                [
                    'id' => Types::INTEGER,
                    'pro_id' => Types::INTEGER,
                    'service_id' => Types::INTEGER,
                    'start_dt' => Types::DATETIME_IMMUTABLE,
                    'end_dt' => Types::DATETIME_IMMUTABLE,
                    'last_name' => Types::STRING,
                    'first_name' => Types::STRING,
                    'email' => Types::STRING,
                ]
            );
            return $id;
        }

        $this->connection->executeStatement(
            'INSERT INTO appointment (pro_id, service_id, start_dt, end_dt, last_name, first_name, email)
             VALUES (:pro_id, :service_id, :start_dt, :end_dt, :last_name, :first_name, :email)',
            [
                'pro_id' => $proId,
                'service_id' => $dto->getServiceId(),
                'start_dt' => $startDt,
                'end_dt' => $endDt,
                'last_name' => $dto->getLastName(),
                'first_name' => $dto->getFirstName(),
                'email' => $dto->getEmail(),
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

    private function hasOverlapForPro(int $proId, DateTimeImmutable $startDt, ?DateTimeImmutable $endDt, ?int $excludeId = null): bool
    {
        $checkEnd = $endDt ?? $startDt;
        $sql = 'SELECT 1 FROM appointment a
                WHERE a.pro_id = :pro_id
                  AND a.deleted_at IS NULL'
            . ($excludeId ? ' AND a.id <> :id' : '') .
            ' AND tstzrange(a.start_dt, COALESCE(a.end_dt, a.start_dt), \'[)\') &&
                      tstzrange(:start_dt::timestamptz, :end_dt::timestamptz, \'[)\')
                LIMIT 1';
        $params = [
            'pro_id' => $proId,
            'start_dt' => $startDt,
            'end_dt' => $checkEnd,
        ];
        $types = [
            'pro_id' => Types::INTEGER,
            'start_dt' => Types::DATETIME_IMMUTABLE,
            'end_dt' => Types::DATETIME_IMMUTABLE,
        ];
        if ($excludeId) {
            $params['id'] = $excludeId;
            $types['id'] = Types::INTEGER;
        }
        return (bool) $this->connection->fetchOne($sql, $params, $types);
    }

    public function softDelete(int $proId, int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE appointment SET deleted_at = NOW() WHERE id = :id AND pro_id = :pro_id AND deleted_at IS NULL',
            [
                'id' => $id,
                'pro_id' => $proId,
            ],
            [
                'id' => Types::INTEGER,
                'pro_id' => Types::INTEGER,
            ]
        );
    }
}
