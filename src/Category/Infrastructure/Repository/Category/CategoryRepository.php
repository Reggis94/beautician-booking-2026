<?php

namespace App\Category\Infrastructure\Repository\Category;

use App\Category\Application\Contract\Repository\CategoryRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
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
