<?php

namespace App\Services\Application\ServiceCategory\QueryHandler;

use App\Services\Application\ServiceCategory\Query\ServiceCategoryListProPresentationQuery;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Service\FrenchNameOrder;

/**
 * Categories on the public page: not deleted categories of a not deleted professional with at least one published,
 * not deleted service, in French alphabetical order. A category has no publication state of its own.
 */
final class ServiceCategoryListProPresentationQueryHandler
{
    public function __construct(private readonly ServiceCategoryRepositoryInterface $categories)
    {
    }

    /**
     * @return list<array{id: int, name: string, services_count: int}>
     */
    public function __invoke(ServiceCategoryListProPresentationQuery $query): array
    {
        $categories = $this->categories->listForProPresentation($query->id);

        usort($categories, static function (array $first, array $second): int {
            return FrenchNameOrder::compareWithId($first['name'], $first['id'], $second['name'], $second['id']);
        });

        return $categories;
    }
}
