<?php

namespace App\Services\Infrastructure\Dao;

use App\Services\Application\ServiceOption\Dao\ServiceOptionDaoInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class ServiceOptionDao implements ServiceOptionDaoInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function create(int $serviceId, string $name, ?int $priceExtraCents): void
    {
        $this->connection->executeStatement(
            'INSERT INTO service_option (service_id, name, price_extra_cents) '
            . 'VALUES (:service_id, :name, :price_extra_cents)',
            [
                'service_id' => $serviceId,
                'name' => $name,
                'price_extra_cents' => $priceExtraCents,
            ],
            [
                'service_id' => Types::INTEGER,
                'name' => Types::STRING,
                'price_extra_cents' => Types::INTEGER,
            ]
        );
    }
}
