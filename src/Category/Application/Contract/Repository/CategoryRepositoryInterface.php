<?php

namespace App\Category\Application\Contract\Repository;

interface CategoryRepositoryInterface
{
    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getListForProPresentation(int $proId): array;
}
