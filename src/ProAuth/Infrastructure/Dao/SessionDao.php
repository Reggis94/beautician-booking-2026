<?php

namespace App\ProAuth\Infrastructure\Dao;

use App\ProAuth\Application\Dao\SessionDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class SessionDao implements SessionDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(int $proId, string $sessionHash): void
    {
        $this->connection->executeStatement(
            'INSERT INTO pro_session (pro_id, session_hash) VALUES (:pro_id, :session_hash)',
            ['pro_id' => $proId, 'session_hash' => $sessionHash],
            ['pro_id' => Types::INTEGER, 'session_hash' => Types::STRING]
        );
    }

    public function revoke(string $sessionHash): void
    {
        $this->connection->executeStatement(
            'UPDATE pro_session SET revoked_at = NOW() WHERE session_hash = :session_hash AND revoked_at IS NULL',
            ['session_hash' => $sessionHash],
            ['session_hash' => Types::STRING]
        );
    }

    public function findActiveProBySessionHash(string $sessionHash): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT p.id, p.email, p.link_slug, p.display_name, p.firstname, p.lastname
             FROM pro_session AS s
             INNER JOIN pro AS p ON p.id = s.pro_id
             WHERE s.session_hash = :session_hash
               AND s.revoked_at IS NULL
               AND p.deleted_at IS NULL
             LIMIT 1',
            ['session_hash' => $sessionHash],
            ['session_hash' => Types::STRING]
        );

        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'link_slug' => (string) $row['link_slug'],
            'display_name' => $row['display_name'] !== null ? (string) $row['display_name'] : null,
            'firstname' => $row['firstname'] !== null ? (string) $row['firstname'] : null,
            'lastname' => $row['lastname'] !== null ? (string) $row['lastname'] : null,
        ];
    }
}
