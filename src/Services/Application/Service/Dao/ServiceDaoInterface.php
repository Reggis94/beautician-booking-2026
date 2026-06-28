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

    public function update(
        int $id,
        ?int $categoryId,
        string $name,
        ?string $description,
        ?int $durationMin,
        ?int $priceCents
    ): void;

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     duration_minutes: ?int,
     *     price_cents: ?int,
     *     currency: string
     * }>
     */
    public function listProAdminServices(int $proId): array;

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int
     * }>
     */
    public function listProPresentationServiceForCategory(int $categoryId): array;
}
