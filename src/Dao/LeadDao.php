<?php

namespace App\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class LeadDao
{
    public function __construct(private readonly Connection $connection) {}

    public function create(
        int $proId,
        string $firstname,
        string $lastname,
        string $phone,
        array $availability = [],
        ?string $ip = null,
        ?string $userAgent = null,
    ): int
    {
        $this->connection->executeStatement(
            'INSERT INTO lead (pro_id, phone, firstname, lastname, availability, ip, user_agent)
             VALUES (:pro_id, :phone, :firstname, :lastname, :availability, :ip, :user_agent)',
            [
                'pro_id' => $proId,
                'phone' => $phone,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'availability' => $availability,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ],
            [
                'pro_id' => Types::INTEGER,
                'phone' => Types::STRING,
                'firstname' => Types::STRING,
                'lastname' => Types::STRING,
                'availability' => Types::JSON,
                'ip' => Types::STRING,
                'user_agent' => Types::STRING,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }
}
