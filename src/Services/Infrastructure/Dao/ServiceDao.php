<?php

namespace App\Services\Infrastructure\Dao;

use App\Services\Application\Service\Dao\ServiceDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ServiceDao implements ServiceDaoInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function create(
        int $proId,
        ?int $categoryId,
        string $name,
        ?string $description,
        ?int $durationMin,
        ?int $priceCents
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO service (pro_id, category_id, name, description, duration_min, price_cents) '
            . 'VALUES (:pro_id, :category_id, :name, :description, :duration_min, :price_cents)',
            [
                'pro_id' => $proId,
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $description,
                'duration_min' => $durationMin,
                'price_cents' => $priceCents,
            ],
            [
                'pro_id' => Types::INTEGER,
                'category_id' => Types::INTEGER,
                'name' => Types::STRING,
                'description' => Types::TEXT,
                'duration_min' => Types::INTEGER,
                'price_cents' => Types::INTEGER,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE service SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL',
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
            'SELECT 1 FROM service WHERE id = :id AND pro_id = :pro_id AND deleted_at IS NULL',
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

    public function update(
        int $id,
        ?int $categoryId,
        string $name,
        ?string $description,
        ?int $durationMin,
        ?int $priceCents
    ): void {
        $this->connection->executeStatement(
            'UPDATE service SET category_id = :category_id, name = :name, description = :description, '
            . 'duration_min = :duration_min, price_cents = :price_cents WHERE id = :id AND deleted_at IS NULL',
            [
                'id' => $id,
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $description,
                'duration_min' => $durationMin,
                'price_cents' => $priceCents,
            ],
            [
                'id' => Types::INTEGER,
                'category_id' => Types::INTEGER,
                'name' => Types::STRING,
                'description' => Types::TEXT,
                'duration_min' => Types::INTEGER,
                'price_cents' => Types::INTEGER,
            ]
        );
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int
     * }>
     */
    public function listProPresentationServiceForCategory(int $categoryId): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT s.id, s.name, s.description, s.price_cents, s.duration_min FROM service s '
            . 'INNER JOIN service_category sc ON sc.id = s.category_id AND sc.pro_id = s.pro_id '
            . 'INNER JOIN pro p ON p.id = s.pro_id '
            . 'WHERE s.category_id = :category_id '
            . 'AND s.deleted_at IS NULL '
            . 'AND sc.deleted_at IS NULL '
            . 'AND p.deleted_at IS NULL '
            . 'ORDER BY s.id ASC',
            [
                'category_id' => $categoryId,
            ],
            [
                'category_id' => Types::INTEGER,
            ]
        );
    }
}
