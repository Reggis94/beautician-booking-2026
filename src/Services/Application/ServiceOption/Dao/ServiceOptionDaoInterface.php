<?php

namespace App\Services\Application\ServiceOption\Dao;

interface ServiceOptionDaoInterface
{
    public function create(
        int $serviceId,
        string $name,
        ?int $priceExtraCents
    ): void;
}
