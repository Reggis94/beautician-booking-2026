<?php

namespace App\Services\Infrastructure\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use App\Services\Application\ServiceCategory\Dao\ServiceCategoryDaoInterface;

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
                'name'   => $name,
            ],
            [
                'pro_id' => Types::INTEGER,
                'name'   => Types::STRING,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE service_category SET deleted_at = NOW() WHERE id = :id',
            [
                'id' => $id
            ],
            [
                'id' => Types::INTEGER
            ]
        );
    }

    public function doesBelongToPro(int $id, int $proId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM service_category WHERE id = :id AND pro_id = :pro_id',
            [
                'id'     => $id,
                'pro_id' => $proId,
            ],
            [
                'id'     => Types::INTEGER,
                'pro_id' => Types::INTEGER,
            ]
        );
    }
}
