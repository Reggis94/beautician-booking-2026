<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Query\GetProAdminServiceCatalogQuery;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\FrenchNameOrder;

/**
 * Builds the ProAdmin catalog: not deleted categories with their not deleted services, then uncategorized services.
 *
 * Order: active categories (at least one published service) first, then inactive ones, each group in
 * French alphabetical order; within a category, published services first, then drafts, alphabetically.
 * Deleted and replaced versions are not listed.
 */
final class GetProAdminServiceCatalogQueryHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories
    ) {
    }

    /**
     * @return array{
     *     categories: list<array{
     *         id: int,
     *         name: string,
     *         is_active: bool,
     *         appointment_count: int,
     *         services: list<array<string, mixed>>
     *     }>,
     *     uncategorized: list<array<string, mixed>>
     * }
     */
    public function __invoke(GetProAdminServiceCatalogQuery $query): array
    {
        $serviceRows = $this->services->listNotDeletedForProAdminCatalog($query->proId);

        $serviceIds = [];
        foreach ($serviceRows as $serviceRow) {
            $serviceIds[] = $serviceRow['id'];
        }
        $imagesByService = $this->services->listActiveImages($serviceIds);

        $servicesByCategory = [];
        $uncategorized = [];
        foreach ($serviceRows as $serviceRow) {
            $serviceRow['images'] = $imagesByService[$serviceRow['id']] ?? [];

            if ($serviceRow['category_id'] === null) {
                $uncategorized[] = $serviceRow;

                continue;
            }

            $servicesByCategory[$serviceRow['category_id']][] = $serviceRow;
        }

        $categories = [];
        foreach ($this->categories->listNotDeletedForProAdminCatalog($query->proId) as $categoryRow) {
            $services = self::sortServices($servicesByCategory[$categoryRow['id']] ?? []);

            $isActive = false;
            foreach ($services as $service) {
                if ($service['published']) {
                    $isActive = true;

                    break;
                }
            }

            $categories[] = [
                'appointment_count' => $categoryRow['appointment_count'],
                'id' => $categoryRow['id'],
                'is_active' => $isActive,
                'name' => $categoryRow['name'],
                'services' => $services,
            ];
        }

        return [
            'categories' => self::sortCategories($categories),
            'uncategorized' => self::sortServices($uncategorized),
        ];
    }

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return list<array<string, mixed>>
     */
    private static function sortCategories(array $categories): array
    {
        usort($categories, static function (array $first, array $second): int {
            if ($first['is_active'] !== $second['is_active']) {
                return $first['is_active'] ? -1 : 1;
            }

            return FrenchNameOrder::compareWithId($first['name'], $first['id'], $second['name'], $second['id']);
        });

        return $categories;
    }

    /**
     * @param list<array<string, mixed>> $services
     *
     * @return list<array<string, mixed>>
     */
    private static function sortServices(array $services): array
    {
        usort($services, static function (array $first, array $second): int {
            if ($first['published'] !== $second['published']) {
                return $first['published'] ? -1 : 1;
            }

            return FrenchNameOrder::compareWithId($first['name'], $first['id'], $second['name'], $second['id']);
        });

        return $services;
    }
}
