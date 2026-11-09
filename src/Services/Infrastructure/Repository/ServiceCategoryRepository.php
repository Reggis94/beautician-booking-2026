<?php

namespace App\Services\Infrastructure\Repository;

use App\Services\Domain\Entity\ServiceCategory;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\ValueObject\DeletionReason;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ServiceCategoryRepository implements ServiceCategoryRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findNotDeleted(int $id, int $proId): ?ServiceCategory
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, pro_id, name, deleted_at, deletion_reason, previous_version_id
             FROM service_category
             WHERE id = :id
               AND pro_id = :pro_id
               AND deleted_at IS NULL',
            ['id' => $id, 'pro_id' => $proId],
            ['id' => Types::INTEGER, 'pro_id' => Types::INTEGER]
        );

        if ($row === false) {
            return null;
        }

        return ServiceCategory::fromPersistence(
            (int) $row['id'],
            (int) $row['pro_id'],
            (string) $row['name'],
            $row['deleted_at'] !== null ? new \DateTimeImmutable((string) $row['deleted_at']) : null,
            $row['deletion_reason'] !== null ? DeletionReason::from((string) $row['deletion_reason']) : null,
            $row['previous_version_id'] !== null ? (int) $row['previous_version_id'] : null
        );
    }

    public function save(ServiceCategory $category): void
    {
        $deletedAt = $category->getDeletedAt()?->format('Y-m-d H:i:s.uP');
        $deletionReason = $category->getDeletionReason()?->value;

        if ($category->getId() !== null) {
            $this->connection->executeStatement(
                'UPDATE service_category
                 SET name = :name,
                     deleted_at = CAST(:deleted_at AS TIMESTAMPTZ),
                     deletion_reason = :deletion_reason
                 WHERE id = :id',
                [
                    'deleted_at' => $deletedAt,
                    'deletion_reason' => $deletionReason,
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ],
                [
                    'deleted_at' => Types::STRING,
                    'deletion_reason' => Types::STRING,
                    'id' => Types::INTEGER,
                    'name' => Types::STRING,
                ]
            );

            return;
        }

        $id = $this->connection->fetchOne(
            'INSERT INTO service_category (pro_id, name, deleted_at, deletion_reason, previous_version_id)
             VALUES (:pro_id, :name, CAST(:deleted_at AS TIMESTAMPTZ), :deletion_reason, :previous_version_id)
             RETURNING id',
            [
                'deleted_at' => $deletedAt,
                'deletion_reason' => $deletionReason,
                'name' => $category->getName(),
                'previous_version_id' => $category->getPreviousVersionId(),
                'pro_id' => $category->getProId(),
            ],
            [
                'deleted_at' => Types::STRING,
                'deletion_reason' => Types::STRING,
                'name' => Types::STRING,
                'previous_version_id' => Types::INTEGER,
                'pro_id' => Types::INTEGER,
            ]
        );
        $category->identify((int) $id);
    }

    public function isNameUsed(int $proId, string $name, ?int $exceptCategoryId): bool
    {
        $sql = 'SELECT 1
                FROM service_category
                WHERE pro_id = :pro_id
                  AND deleted_at IS NULL
                  AND LOWER(TRIM(name)) = LOWER(TRIM(:name))';
        $parameters = ['name' => $name, 'pro_id' => $proId];
        $types = ['name' => Types::STRING, 'pro_id' => Types::INTEGER];

        if ($exceptCategoryId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptCategoryId;
            $types['except_id'] = Types::INTEGER;
        }

        return (bool) $this->connection->fetchOne($sql . ' LIMIT 1', $parameters, $types);
    }

    public function countAppointments(int $categoryId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM appointment a
             INNER JOIN service s ON s.id = a.service_id
             WHERE s.category_id = :category_id',
            ['category_id' => $categoryId],
            ['category_id' => Types::INTEGER]
        );
    }

    public function listNotDeletedForProAdminCatalog(int $proId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT sc.id,
                    sc.name,
                    (
                        SELECT COUNT(*)
                        FROM appointment a
                        INNER JOIN service s ON s.id = a.service_id
                        WHERE s.category_id = sc.id
                    ) AS appointment_count
             FROM service_category sc
             WHERE sc.pro_id = :pro_id
               AND sc.deleted_at IS NULL',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = [
                'appointment_count' => (int) $row['appointment_count'],
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $categories;
    }

    public function listForProPresentation(int $proId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT sc.id, sc.name, COUNT(s.id) AS services_count
             FROM service_category sc
             INNER JOIN pro p ON p.id = sc.pro_id AND p.deleted_at IS NULL
             INNER JOIN service s
                     ON s.category_id = sc.id
                    AND s.pro_id = sc.pro_id
                    AND s.deleted_at IS NULL
                    AND s.published_at IS NOT NULL
             WHERE sc.pro_id = :pro_id
               AND sc.deleted_at IS NULL
             GROUP BY sc.id, sc.name',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'services_count' => (int) $row['services_count'],
            ];
        }

        return $categories;
    }
}
