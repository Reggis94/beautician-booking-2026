<?php

namespace App\Services\Infrastructure\Dao;

use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ServiceCategoryDao implements ServiceCategoryDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(int $proId, string $name): void
    {
        $this->connection->executeStatement(
            'INSERT INTO service_category (pro_id, name) VALUES (:pro_id, :name)',
            [
                'pro_id' => $proId,
                'name' => $name,
            ],
            [
                'pro_id' => Types::INTEGER,
                'name' => Types::STRING,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE service_category SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [
                'id' => $id,
            ],
            [
                'id' => Types::INTEGER,
            ]
        );
    }

    public function doesBelongToPro(int $id, int $proId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM service_category WHERE id = :id AND pro_id = :pro_id AND deleted_at IS NULL',
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

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getListForProPresentation(int $proId): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT id, name FROM service_category '
            . 'WHERE pro_id = :pro_id AND deleted_at IS NULL '
            . 'ORDER BY id ASC',
            [
                'pro_id' => $proId,
            ],
            [
                'pro_id' => Types::INTEGER,
            ]
        );
    }
}
