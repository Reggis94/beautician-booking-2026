<?php

namespace App\Services\Domain\Repository;

use App\Services\Domain\Entity\ServiceCategory;

interface ServiceCategoryRepositoryInterface
{
    /**
     * A not deleted category (neither deleted nor replaced) of this professional.
     */
    public function findNotDeleted(int $id, int $proId): ?ServiceCategory;

    /**
     * Inserts a new version (and identifies it) or updates an existing one.
     */
    public function save(ServiceCategory $category): void;

    /**
     * Case-insensitive check among the professional's not deleted categories.
     */
    public function isNameUsed(int $proId, string $name, ?int $exceptCategoryId): bool;

    /**
     * Appointments booked on any service version of this category version, whatever their state.
     */
    public function countAppointments(int $categoryId): int;

    /**
     * @return list<array{id: int, name: string, appointment_count: int}>
     */
    public function listNotDeletedForProAdminCatalog(int $proId): array;

    /**
     * Not deleted categories of a not deleted professional that contain at least one published, not deleted service.
     *
     * @return list<array{id: int, name: string, services_count: int}>
     */
    public function listForProPresentation(int $proId): array;
}
