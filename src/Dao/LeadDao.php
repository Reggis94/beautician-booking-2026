<?php

namespace App\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class LeadDao
{
    public function __construct(private readonly Connection $connection) {}

    public function create(int $proId, string $firstname, string $lastname, string $phone): int
    {
        $this->connection->executeStatement(
            'INSERT INTO lead (pro_id, phone, firstname, lastname) VALUES (:pro_id, :phone, :firstname, :lastname)',
            [
                'pro_id' => $proId,
                'phone' => $phone,
                'firstname' => $firstname,
                'lastname' => $lastname,
            ],
            [
                'pro_id' => Types::INTEGER,
                'phone' => Types::STRING,
                'firstname' => Types::STRING,
                'lastname' => Types::STRING,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }
}

