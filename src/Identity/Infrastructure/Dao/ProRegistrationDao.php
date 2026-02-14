<?php

namespace App\Identity\Infrastructure\Dao;

use App\Identity\Application\Dao\ProRegistrationDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ProRegistrationDao implements ProRegistrationDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(string $firstName, string $lastName, string $email, string $confirmationToken): void
    {
        $this->connection->executeStatement(
            'INSERT INTO pro (first_name, last_name, email, confirmation_token, confirmation_token_sent_at)
             VALUES (:first_name, :last_name, :email, :confirmation_token, NOW())',
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'confirmation_token' => $confirmationToken,
            ],
            [
                'first_name' => Types::STRING,
                'last_name' => Types::STRING,
                'email' => Types::STRING,
                'confirmation_token' => Types::STRING,
            ]
        );
    }
}
