<?php

namespace App\Services\Domain\Repository;

use App\Services\Domain\Entity\Service;

interface ServiceRepositoryInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    /**
     * Serializes catalog writes of one professional with each other and with new bookings, which take
     * the same lock. Must be called inside a transaction.
     */
    public function lockCatalog(int $proId): void;

    /**
     * A not deleted service (draft or published, neither deleted nor replaced) of this professional, with its
     * active images.
     *
     * @param bool $withAppointmentCount Also loads the appointment count, needed to call hasAppointments().
     */
    public function findNotDeleted(int $id, int $proId, bool $withAppointmentCount = false): ?Service;

    /**
     * @param bool $withAppointmentCount Also loads the appointment count, needed to call hasAppointments().
     *
     * @return list<Service>
     */
    public function findNotDeletedInCategory(int $proId, int $categoryId, bool $withAppointmentCount = false): array;

    /**
     * Inserts a new version (and identifies it) or updates an existing one, with its image associations.
     */
    public function save(Service $service): void;

    /**
     * Not deleted services (drafts and published). category_id is null without a not deleted category.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     duration_min: ?int,
     *     price_cents: ?int,
     *     category_id: ?int,
     *     published: bool,
     *     appointment_count: int
     * }>
     */
    public function listNotDeletedForProAdminCatalog(int $proId): array;

    /**
     * Active images of the given services, keyed by service id.
     *
     * @param list<int> $serviceIds
     *
     * @return array<int, list<array{id: int, file_name: string}>>
     */
    public function listActiveImages(array $serviceIds): array;

    /**
     * Whether this file name is an active image of a service of this professional.
     */
    public function isActiveImage(int $proId, string $fileName): bool;

    /**
     * Published, not deleted services of a not deleted category of a not deleted professional.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int
     * }>
     */
    public function listPublishedInCategory(int $proId, int $categoryId): array;

    /**
     * Published, not deleted services of a not deleted professional without a not deleted category.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int
     * }>
     */
    public function listPublishedUncategorized(int $proId): array;
}
