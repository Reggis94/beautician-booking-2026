<?php

namespace App\Services\Application\ServiceOption\Command;

final class CreateServiceOptionCommand
{
    public function __construct(
        public readonly int $proId,
        public readonly int $serviceId,
        public readonly string $name,
        public readonly ?int $priceExtraCents
    ) {
    }
}
