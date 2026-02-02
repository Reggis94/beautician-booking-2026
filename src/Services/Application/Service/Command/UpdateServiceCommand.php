<?php

namespace App\Services\Application\Service\Command;

final class UpdateServiceCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $proId,
        public readonly ?int $categoryId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $durationMin,
        public readonly ?int $priceCents
    ) {
    }
}
