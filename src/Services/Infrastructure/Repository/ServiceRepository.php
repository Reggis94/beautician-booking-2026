<?php

namespace App\Services\Infrastructure\Repository;

use App\Services\Domain\Entity\Service;
use App\Services\Domain\Entity\ServiceImage;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\ValueObject\DeletionReason;
use App\Services\Domain\ValueObject\ServiceContent;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ServiceRepository implements ServiceRepositoryInterface
{
    private const SERVICE_COLUMNS = 'id, pro_id, category_id, name, description, duration_min, price_cents, '
        . 'published_at, deleted_at, deletion_reason, previous_version_id';
    private const APPOINTMENT_COUNT_COLUMN = ', (SELECT COUNT(*) FROM appointment WHERE appointment.service_id = '
        . 'service.id) AS appointment_count';

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

    public function lockCatalog(int $proId): void
    {
        // Same row lock as Booking's AppointmentRepository::lockProAppointments(): catalog writes and new
        // bookings of one professional run one after the other.
        $this->connection->fetchOne(
            'SELECT id FROM pro WHERE id = :pro_id FOR UPDATE',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );
    }

    public function findNotDeleted(int $id, int $proId, bool $withAppointmentCount = false): ?Service
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::serviceColumns($withAppointmentCount) . '
             FROM service
             WHERE id = :id
               AND pro_id = :pro_id
               AND deleted_at IS NULL',
            ['id' => $id, 'pro_id' => $proId],
            ['id' => Types::INTEGER, 'pro_id' => Types::INTEGER]
        );

        if ($row === false) {
            return null;
        }

        $services = $this->hydrate([$row]);

        return $services[0];
    }

    public function findNotDeletedInCategory(int $proId, int $categoryId, bool $withAppointmentCount = false): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ' . self::serviceColumns($withAppointmentCount) . '
             FROM service
             WHERE pro_id = :pro_id
               AND category_id = :category_id
               AND deleted_at IS NULL
             ORDER BY id ASC',
            ['pro_id' => $proId, 'category_id' => $categoryId],
            ['pro_id' => Types::INTEGER, 'category_id' => Types::INTEGER]
        );

        return $this->hydrate($rows);
    }

    public function save(Service $service): void
    {
        $content = $service->getContent();
        $parameters = [
            'category_id' => $content->getCategoryId(),
            'deleted_at' => self::formatDateTime($service->getDeletedAt()),
            'deletion_reason' => $service->getDeletionReason()?->value,
            'description' => $content->getDescription(),
            'duration_min' => $content->getDurationMin(),
            'name' => $content->getName(),
            'price_cents' => $content->getPriceCents(),
            'published_at' => self::formatDateTime($service->getPublishedAt()),
        ];
        $types = [
            'category_id' => Types::INTEGER,
            'deleted_at' => Types::STRING,
            'deletion_reason' => Types::STRING,
            'description' => Types::TEXT,
            'duration_min' => Types::INTEGER,
            'name' => Types::STRING,
            'price_cents' => Types::INTEGER,
            'published_at' => Types::STRING,
        ];

        if ($service->getId() === null) {
            $id = $this->connection->fetchOne(
                'INSERT INTO service (
                    pro_id, category_id, name, description, duration_min, price_cents,
                    published_at, deleted_at, deletion_reason, previous_version_id
                 )
                 VALUES (
                    :pro_id, :category_id, :name, :description, :duration_min, :price_cents,
                    CAST(:published_at AS TIMESTAMPTZ), CAST(:deleted_at AS TIMESTAMPTZ), :deletion_reason,
                    :previous_version_id
                 )
                 RETURNING id',
                $parameters + [
                    'previous_version_id' => $service->getPreviousVersionId(),
                    'pro_id' => $service->getProId(),
                ],
                $types + [
                    'previous_version_id' => Types::INTEGER,
                    'pro_id' => Types::INTEGER,
                ]
            );
            $service->identify((int) $id);
        } else {
            $this->connection->executeStatement(
                'UPDATE service
                 SET category_id = :category_id,
                     name = :name,
                     description = :description,
                     duration_min = :duration_min,
                     price_cents = :price_cents,
                     published_at = CAST(:published_at AS TIMESTAMPTZ),
                     deleted_at = CAST(:deleted_at AS TIMESTAMPTZ),
                     deletion_reason = :deletion_reason
                 WHERE id = :id',
                $parameters + ['id' => $service->getId()],
                $types + ['id' => Types::INTEGER]
            );
        }

        $this->saveImages((int) $service->getId(), $service->getImages());
    }

    public function listNotDeletedForProAdminCatalog(int $proId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT s.id,
                    s.name,
                    s.description,
                    s.duration_min,
                    s.price_cents,
                    sc.id AS category_id,
                    s.published_at IS NOT NULL AS published,
                    (SELECT COUNT(*) FROM appointment a WHERE a.service_id = s.id) AS appointment_count
             FROM service s
             LEFT JOIN service_category sc
                    ON sc.id = s.category_id
                   AND sc.pro_id = s.pro_id
                   AND sc.deleted_at IS NULL
             WHERE s.pro_id = :pro_id
               AND s.deleted_at IS NULL',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        $services = [];
        foreach ($rows as $row) {
            $services[] = [
                'appointment_count' => (int) $row['appointment_count'],
                'category_id' => self::nullableInt($row['category_id']),
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'duration_min' => self::nullableInt($row['duration_min']),
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'price_cents' => self::nullableInt($row['price_cents']),
                'published' => (bool) $row['published'],
            ];
        }

        return $services;
    }

    public function listActiveImages(array $serviceIds): array
    {
        if ($serviceIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, service_id, file_name
             FROM service_image
             WHERE service_id IN (:service_ids)
               AND deleted_at IS NULL
             ORDER BY id ASC',
            ['service_ids' => $serviceIds],
            ['service_ids' => ArrayParameterType::INTEGER]
        );

        $images = [];
        foreach ($rows as $row) {
            $images[(int) $row['service_id']][] = [
                'file_name' => (string) $row['file_name'],
                'id' => (int) $row['id'],
            ];
        }

        return $images;
    }

    public function isActiveImage(int $proId, string $fileName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1
             FROM service_image si
             INNER JOIN service s ON s.id = si.service_id
             WHERE si.file_name = :file_name
               AND si.deleted_at IS NULL
               AND s.pro_id = :pro_id',
            ['file_name' => $fileName, 'pro_id' => $proId],
            ['file_name' => Types::STRING, 'pro_id' => Types::INTEGER]
        );
    }

    public function listPublishedInCategory(int $proId, int $categoryId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT s.id, s.name, s.description, s.price_cents, s.duration_min
             FROM service s
             INNER JOIN service_category sc ON sc.id = s.category_id AND sc.pro_id = s.pro_id
             INNER JOIN pro p ON p.id = s.pro_id
             WHERE s.pro_id = :pro_id
               AND s.category_id = :category_id
               AND s.deleted_at IS NULL
               AND s.published_at IS NOT NULL
               AND sc.deleted_at IS NULL
               AND p.deleted_at IS NULL',
            ['pro_id' => $proId, 'category_id' => $categoryId],
            ['pro_id' => Types::INTEGER, 'category_id' => Types::INTEGER]
        );

        return self::presentationRows($rows);
    }

    public function listPublishedUncategorized(int $proId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT s.id, s.name, s.description, s.price_cents, s.duration_min
             FROM service s
             INNER JOIN pro p ON p.id = s.pro_id
             LEFT JOIN service_category sc
                    ON sc.id = s.category_id
                   AND sc.pro_id = s.pro_id
                   AND sc.deleted_at IS NULL
             WHERE s.pro_id = :pro_id
               AND s.deleted_at IS NULL
               AND s.published_at IS NOT NULL
               AND p.deleted_at IS NULL
               AND sc.id IS NULL',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        return self::presentationRows($rows);
    }

    /**
     * Saving an existing image also sets its service, so the images taken over by a new service version move
     * to that version.
     *
     * @param list<ServiceImage> $images
     */
    private function saveImages(int $serviceId, array $images): void
    {
        foreach ($images as $image) {
            $parameters = [
                'deleted_at' => self::formatDateTime($image->getDeletedAt()),
                'service_id' => $serviceId,
            ];
            $types = [
                'deleted_at' => Types::STRING,
                'service_id' => Types::INTEGER,
            ];

            if ($image->getId() !== null) {
                $this->connection->executeStatement(
                    'UPDATE service_image
                     SET service_id = :service_id,
                         deleted_at = CAST(:deleted_at AS TIMESTAMPTZ)
                     WHERE id = :id',
                    $parameters + ['id' => $image->getId()],
                    $types + ['id' => Types::INTEGER]
                );

                continue;
            }

            $id = $this->connection->fetchOne(
                'INSERT INTO service_image (service_id, file_name, deleted_at)
                 VALUES (:service_id, :file_name, CAST(:deleted_at AS TIMESTAMPTZ))
                 RETURNING id',
                $parameters + ['file_name' => $image->getFileName()],
                $types + ['file_name' => Types::STRING]
            );
            $image->identify((int) $id);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<Service>
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $serviceIds = [];
        foreach ($rows as $row) {
            $serviceIds[] = (int) $row['id'];
        }

        $imageRows = $this->connection->fetchAllAssociative(
            'SELECT id, service_id, file_name, deleted_at
             FROM service_image
             WHERE service_id IN (:service_ids)
               AND deleted_at IS NULL
             ORDER BY id ASC',
            ['service_ids' => $serviceIds],
            ['service_ids' => ArrayParameterType::INTEGER]
        );

        $imagesByService = [];
        foreach ($imageRows as $imageRow) {
            $imagesByService[(int) $imageRow['service_id']][] = ServiceImage::fromPersistence(
                (int) $imageRow['id'],
                (string) $imageRow['file_name'],
                self::parseDateTime($imageRow['deleted_at'])
            );
        }

        $services = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $services[] = Service::fromPersistence(
                $id,
                (int) $row['pro_id'],
                new ServiceContent(
                    (string) $row['name'],
                    $row['description'] !== null ? (string) $row['description'] : null,
                    self::nullableInt($row['duration_min']),
                    self::nullableInt($row['price_cents']),
                    self::nullableInt($row['category_id'])
                ),
                self::parseDateTime($row['published_at']),
                self::parseDateTime($row['deleted_at']),
                $row['deletion_reason'] !== null ? DeletionReason::from((string) $row['deletion_reason']) : null,
                self::nullableInt($row['previous_version_id']),
                $imagesByService[$id] ?? [],
                self::nullableInt($row['appointment_count'] ?? null)
            );
        }

        return $services;
    }

    private static function serviceColumns(bool $withAppointmentCount): string
    {
        return self::SERVICE_COLUMNS . ($withAppointmentCount ? self::APPOINTMENT_COUNT_COLUMN : '');
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{id: int, name: string, description: ?string, price_cents: ?int, duration_min: ?int}>
     */
    private static function presentationRows(array $rows): array
    {
        $services = [];
        foreach ($rows as $row) {
            $services[] = [
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'duration_min' => self::nullableInt($row['duration_min']),
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'price_cents' => self::nullableInt($row['price_cents']),
            ];
        }

        return $services;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    private static function formatDateTime(?\DateTimeImmutable $value): ?string
    {
        return $value?->format('Y-m-d H:i:s.uP');
    }

    private static function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return new \DateTimeImmutable((string) $value);
    }
}
