<?php

namespace App\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class ProDao implements ProDaoInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function existsById(int $id): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT 1 FROM pro WHERE id = :id AND deleted_at IS NULL',
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        return $result !== false;
    }
    public function findIdByLinkSlug(string $slug): ?int
    {
        $sql = 'SELECT id FROM pro WHERE link_slug = :slug AND deleted_at IS NULL LIMIT 1';
        $id = $this->connection->fetchOne($sql, ['slug' => $slug], ['slug' => Types::STRING]);
        return $id !== false ? (int) $id : null;
    }

    public function findFirstLinkSlug(): ?string
    {
        $slug = $this->connection->fetchOne('SELECT link_slug FROM pro WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1');
        return ($slug !== false && $slug !== null && $slug !== '') ? (string) $slug : null;
    }
}
