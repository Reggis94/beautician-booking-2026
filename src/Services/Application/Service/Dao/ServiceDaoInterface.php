<?php

namespace App\Services\Application\Service\Dao;

interface ServiceDaoInterface
{
    public function create(
        int $proId,
        ?int $categoryId,
        string $name,
        ?string $description,
        ?int $durationMin,
        ?int $priceCents
    ): void;

    public function delete(int $id): void;

    public function doesBelongToPro(int $id, int $proId): bool;
}
