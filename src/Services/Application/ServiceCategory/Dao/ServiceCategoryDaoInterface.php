<?php

namespace App\Services\Application\ServiceCategory\Dao;

interface ServiceCategoryDaoInterface
{
    public function create(int $proId, string $name): void;
    public function delete(int $id): void;
    public function doesBelongToPro(int $id, int $proId): bool;

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getListForProPresentation(int $proId): array;
}
