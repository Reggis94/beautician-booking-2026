<?php

namespace App\ProAuth\Infrastructure\Dao;

use App\ProAuth\Application\Dao\LoginTokenDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class LoginTokenDao implements LoginTokenDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function findActiveProIdByEmail(string $email): ?int
    {
        $proId = $this->connection->fetchOne(
            'SELECT id FROM pro WHERE LOWER(email) = :email AND deleted_at IS NULL LIMIT 1',
            ['email' => $email],
            ['email' => Types::STRING]
        );

        return $proId !== false ? (int) $proId : null;
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

    public function lockPro(int $proId): void
    {
        $this->connection->fetchOne(
            'SELECT id FROM pro WHERE id = :pro_id FOR UPDATE',
            ['pro_id' => $proId],
            ['pro_id' => Types::INTEGER]
        );
    }

    public function existsCreatedWithinSeconds(int $proId, int $seconds): bool
    {
        // created_at and NOW() are both TIMESTAMPTZ instants, so the database session timezone does not matter.
        return (bool) $this->connection->fetchOne(
            'SELECT EXISTS (
                SELECT 1 FROM pro_login_token
                 WHERE pro_id = :pro_id
                   AND created_at > NOW() - make_interval(secs => :seconds)
             )',
            ['pro_id' => $proId, 'seconds' => $seconds],
            ['pro_id' => Types::INTEGER, 'seconds' => Types::INTEGER]
        );
    }

    public function countCreatedWithinSeconds(int $proId, int $seconds): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM pro_login_token
              WHERE pro_id = :pro_id
                AND created_at > NOW() - make_interval(secs => :seconds)',
            ['pro_id' => $proId, 'seconds' => $seconds],
            ['pro_id' => Types::INTEGER, 'seconds' => Types::INTEGER]
        );
    }

    public function create(int $proId, string $tokenHash): void
    {
        $this->connection->executeStatement(
            'INSERT INTO pro_login_token (pro_id, token_hash) VALUES (:pro_id, :token_hash)',
            ['pro_id' => $proId, 'token_hash' => $tokenHash],
            ['pro_id' => Types::INTEGER, 'token_hash' => Types::STRING]
        );
    }

    public function markDeliveryFailed(string $tokenHash): void
    {
        $this->connection->executeStatement(
            'UPDATE pro_login_token
                SET delivery_failed_at = NOW()
              WHERE token_hash = :token_hash
                AND consumed_at IS NULL
                AND delivery_failed_at IS NULL',
            ['token_hash' => $tokenHash],
            ['token_hash' => Types::STRING]
        );
    }

    public function consume(string $tokenHash): ?int
    {
        // A single UPDATE is atomic: of two concurrent requests, only the first one sees consumed_at IS NULL.
        $proId = $this->connection->fetchOne(
            'UPDATE pro_login_token AS t
             SET consumed_at = NOW()
             FROM pro AS p
             WHERE t.token_hash = :token_hash
               AND t.consumed_at IS NULL
               AND t.delivery_failed_at IS NULL
               AND p.id = t.pro_id
               AND p.deleted_at IS NULL
             RETURNING t.pro_id',
            ['token_hash' => $tokenHash],
            ['token_hash' => Types::STRING]
        );

        return $proId !== false ? (int) $proId : null;
    }
}
