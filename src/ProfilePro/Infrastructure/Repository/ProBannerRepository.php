<?php

namespace App\ProfilePro\Infrastructure\Repository;

use App\ProfilePro\Application\Banner\Repository\ProBannerRepositoryInterface;
use App\ProfilePro\Public\Exception\ProNotFound;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ProBannerRepository implements ProBannerRepositoryInterface
{
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

    public function assertProExists(int $proId): void
    {
        $this->assertProExistsInternal($proId, false);
    }

    public function assertProExistsForUpdate(int $proId): void
    {
        $this->assertProExistsInternal($proId, true);
    }

    private function assertProExistsInternal(int $proId, bool $forUpdate): void
    {
        $query = 'SELECT 1 FROM pro WHERE id = :pro_id AND deleted_at IS NULL';
        if ($forUpdate) {
            $query .= ' FOR UPDATE';
        }

        $exists = $this->connection->fetchOne(
            $query,
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        if ($exists === false) {
            throw new ProNotFound();
        }
    }

    public function countActiveForPro(int $proId): int
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM pro_banner WHERE pro_id = :pro_id AND deleted_at IS NULL',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );

        return (int) $count;
    }

    public function create(int $proId, int $orderNumber, string $fileKey, string $commitId): int
    {
        $this->connection->executeStatement(
            'INSERT INTO pro_banner (pro_id, order_number, file_key, commit_id)
             VALUES (:pro_id, :order_number, :file_key, :commit_id)',
            [
                'pro_id' => $proId,
                'order_number' => $orderNumber,
                'file_key' => $fileKey,
                'commit_id' => $commitId,
            ],
            [
                'pro_id' => Types::INTEGER,
                'order_number' => Types::INTEGER,
                'file_key' => Types::STRING,
                'commit_id' => Types::STRING,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }
}
