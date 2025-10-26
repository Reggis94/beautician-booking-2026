<?php

namespace App\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class ProDao
{
    public function __construct(private readonly Connection $connection) {}

    public function findIdByLinkSlug(string $slug): ?int
    {
        $sql = 'SELECT id FROM pro WHERE link_slug = :slug AND deleted_at IS NULL LIMIT 1';
        $id = $this->connection->fetchOne($sql, ['slug' => $slug], ['slug' => Types::STRING]);
        return $id !== false ? (int) $id : null;
    }
}

